<?php

namespace App\Livewire\Dashboard;

use App\Services\StudentDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyProgress extends Component
{
    #[Computed]
    public function courseProgress()
    {
        return app(StudentDashboardService::class)->courseProgress((string) Auth::id());
    }

    public function render()
    {
        return view('livewire.dashboard.my-progress');
    }
}
