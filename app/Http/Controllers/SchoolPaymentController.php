<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\XenditChannel;
use App\Models\PaymentTransaction;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Services\PaymentGatewayFactory;
use App\Services\PaymentGateways\XenditGateway;
use App\Services\PaymentStatusStreamService;
use App\Services\SchoolService;
use App\Support\RootDomains;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
    ) {}

    public function index(PaymentTransaction $transaction, Request $request): View|RedirectResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
            $suffix = RootDomains::suffixFor(RootDomains::match($request->getHost()));

            return redirect()->route("manage.schools.index{$suffix}");
        }

        // Already initiated a payment request against a channel: show the
        // customer their payment instructions, but still pass the available
        // channels so they can switch to a different payment method.
        $selectedChannel = ($transaction->channel && $transaction->status === PaymentStatus::Pending)
            ? XenditChannel::tryFrom($transaction->channel)
            : null;

        $gateway = $this->gatewayRepository->findFirstEnabled();
        abort_if(! $gateway && ! $selectedChannel, 500, 'No payment gateway is currently configured.');

        $channels = $gateway
            ? collect($gateway->enabled_channels ?? [])->map(fn (string $value) => XenditChannel::tryFrom($value))->filter()
            : collect();

        return view('school-payment', [
            'transaction' => $transaction,
            'selectedChannel' => $selectedChannel,
            'channels' => $channels,
            'initialResult' => $selectedChannel ? $this->channelPayload($transaction) : null,
        ]);
    }

    public function confirm(PaymentTransaction $transaction, SchoolService $schoolService, Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        $validated = $request->validate([
            'channel' => ['nullable', Rule::enum(XenditChannel::class)],
        ]);

        $invoice = $schoolService->initiateRegistrationPayment($transaction, $validated['channel'] ?? null);

        if (! ($invoice['success'] ?? false)) {
            $message = $invoice['error'] ?? 'Unable to initiate payment. Please try again.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['channel' => $message]);
        }

        $transaction->refresh();

        $selectedChannel = XenditChannel::tryFrom($transaction->channel ?? '');

        // Redirect-based flows (e.g. Midtrans's hosted Snap page) send the
        // customer off-site immediately. Xendit e-wallet channels (OVO, DANA,
        // ...) instead stay on this page like every other channel — the
        // e-wallet deeplink is only opened when the customer clicks
        // "Simulate Payment", via `payment_instructions` below.
        $offSiteUrl = $selectedChannel?->viewType() !== 'ewallet'
            && ($invoice['payment_url'] ?? null)
            && filter_var($invoice['payment_url'], FILTER_VALIDATE_URL)
            ? $invoice['payment_url']
            : null;

        if ($request->wantsJson()) {
            return response()->json([
                'redirect_url' => $offSiteUrl,
                ...$this->channelPayload($transaction),
            ]);
        }

        if ($offSiteUrl) {
            return redirect()->away($offSiteUrl);
        }

        return redirect()->route('school.payment.index', $transaction);
    }

    /**
     * Trigger Xendit's test-mode "simulate payment" endpoint for a pending,
     * sandbox-mode transaction. Only moves the transaction to "paid" on
     * Xendit's side — the actual status flip happens later via webhook.
     */
    public function simulate(PaymentTransaction $transaction, PaymentGatewayFactory $gatewayFactory): JsonResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        $gateway = $transaction->paymentGateway;
        $channel = XenditChannel::tryFrom($transaction->channel ?? '');

        abort_unless($transaction->status === PaymentStatus::Pending, 422);
        abort_unless($gateway?->is_sandbox_mode, 422, 'This gateway is not in sandbox mode.');
        abort_unless($channel?->supportsSimulation(), 422, 'This payment method cannot be simulated.');

        $gatewayInstance = $gatewayFactory->make($gateway->paymentGatewayType->name, $gateway);
        abort_unless($gatewayInstance instanceof XenditGateway, 422, 'Payment simulation is only supported for Xendit gateways.');

        $result = $gatewayInstance->simulatePayment($transaction->transaction_id, (float) $transaction->amount);

        if (! ($result['success'] ?? false)) {
            return response()->json(['message' => $result['error'] ?? 'Unable to simulate payment.'], 422);
        }

        return response()->json(['message' => $result['message'] ?? 'Payment simulation triggered.']);
    }

    public function stream(PaymentTransaction $transaction, Request $request, PaymentStatusStreamService $streamService): StreamedResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        $suffix = RootDomains::suffixFor(RootDomains::match($request->getHost()));

        return $streamService->stream($transaction, route("manage.schools.index{$suffix}"));
    }

    /**
     * @return array<string, mixed>
     */
    private function channelPayload(PaymentTransaction $transaction): array
    {
        $selectedChannel = XenditChannel::tryFrom($transaction->channel ?? '');

        return [
            'channel' => $selectedChannel?->value,
            'channel_label' => $selectedChannel?->label(),
            'channel_logo' => $selectedChannel?->logoUrl(),
            'view_type' => $selectedChannel?->viewType(),
            'payment_instructions' => $transaction->payment_instructions,
            'guide_steps' => $selectedChannel?->paymentGuideSteps() ?? [],
            'is_sandbox' => (bool) $transaction->paymentGateway?->is_sandbox_mode,
            'supports_simulation' => (bool) $selectedChannel?->supportsSimulation(),
            'simulate_url' => route('school.payment.simulate', $transaction),
            'stream_url' => route('school.payment.stream', $transaction),
        ];
    }
}
