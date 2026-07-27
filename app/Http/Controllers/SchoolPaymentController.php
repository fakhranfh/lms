<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolPaymentController extends Controller
{
    public function index(PaymentTransaction $transaction): View|RedirectResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        if ($transaction->status === PaymentStatus::Completed && $transaction->school) {
            return redirect()->route('manage.schools.index');
        }

        return view('school-payment', [
            'transaction' => $transaction,
        ]);
    }

    public function confirm(PaymentTransaction $transaction, SchoolService $schoolService): RedirectResponse
    {
        abort_unless($transaction->initiated_by === auth()->id(), 403);

        $schoolService->completeRegistrationTransaction($transaction);

        return redirect()->route('manage.schools.index');
    }
}
