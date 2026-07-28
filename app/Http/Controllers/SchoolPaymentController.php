<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Services\SchoolService;
use App\Support\RootDomains;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolPaymentController extends Controller
{
    public function index(PaymentTransaction $transaction, Request $request): View|RedirectResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
            $suffix = RootDomains::suffixFor(RootDomains::match($request->getHost()));

            return redirect()->route("manage.schools.index{$suffix}");
        }

        return view('school-payment', [
            'transaction' => $transaction,
        ]);
    }

    public function confirm(PaymentTransaction $transaction, SchoolService $schoolService, Request $request): RedirectResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        $schoolService->completeRegistrationTransaction($transaction);

        $suffix = RootDomains::suffixFor(RootDomains::match($request->getHost()));

        return redirect()->route("manage.schools.index{$suffix}");
    }
}
