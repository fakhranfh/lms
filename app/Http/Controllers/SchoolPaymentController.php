<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Repositories\School\SchoolRepositoryInterface;
use Illuminate\View\View;

class SchoolPaymentController extends Controller
{
    public function index(School $school, SchoolRepositoryInterface $schoolRepository): View
    {
        abort_unless($schoolRepository->administers($school, auth()->id()), 403);

        return view('school-payment', [
            'school' => $school->load('tier'),
        ]);
    }
}
