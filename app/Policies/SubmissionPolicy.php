<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy extends BasePolicy
{
    public function view(User $user, Submission $submission): bool
    {
        if ($submission->user_id === $user->id) {
            return $this->userHasPermission($user, 'submissions.view');
        }

        return $this->userHasPermission($user, 'submissions.grade');
    }

    public function override(User $user, Submission $submission): bool
    {
        return $this->userHasPermission($user, 'submissions.override-grade');
    }
}
