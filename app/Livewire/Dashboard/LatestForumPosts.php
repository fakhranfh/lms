<?php

namespace App\Livewire\Dashboard;

use App\Services\StudentDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LatestForumPosts extends Component
{
    #[Computed]
    public function forumPosts()
    {
        return app(StudentDashboardService::class)->latestForumPosts((string) Auth::id());
    }

    public function render()
    {
        return view('livewire.dashboard.latest-forum-posts');
    }
}
