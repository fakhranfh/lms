<?php

use App\Models\School;
use App\Models\User;
use App\Support\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CountSchoolUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $result;

    public function __construct(public string $schoolId) {}

    public function handle(): void
    {
        app(CurrentSchool::class)->setSchoolId($this->schoolId);

        $this->result = User::count();
    }
}

test('a queued job scopes queries to the school_id carried in its payload', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();

    User::factory()->forSchool($schoolA)->count(2)->create();
    User::factory()->forSchool($schoolB)->count(3)->create();

    $job = new CountSchoolUsersJob($schoolA->id);
    $job->handle();

    expect($job->result)->toBe(2);
});
