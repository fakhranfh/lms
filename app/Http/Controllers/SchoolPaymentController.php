<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\XenditChannel;
use App\Models\PaymentTransaction;
use App\Repositories\PaymentGateway\PaymentGatewayRepositoryInterface;
use App\Services\SchoolService;
use App\Support\RootDomains;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        // Redirect-based flows (e.g. Midtrans's hosted Snap page, e-wallet
        // deeplinks) send the customer off-site to finish paying.
        $offSiteUrl = ($invoice['payment_url'] ?? null) && filter_var($invoice['payment_url'], FILTER_VALIDATE_URL)
            ? $invoice['payment_url']
            : null;

        if ($request->wantsJson()) {
            $selectedChannel = XenditChannel::tryFrom($transaction->channel ?? '');

            return response()->json([
                'redirect_url' => $offSiteUrl,
                'channel' => $selectedChannel?->value,
                'channel_label' => $selectedChannel?->label(),
                'channel_logo' => $selectedChannel?->logoUrl(),
                'view_type' => $selectedChannel?->viewType(),
                'payment_instructions' => $transaction->payment_instructions,
            ]);
        }

        if ($offSiteUrl) {
            return redirect()->away($offSiteUrl);
        }

        return redirect()->route('school.payment.index', $transaction);
    }
}
