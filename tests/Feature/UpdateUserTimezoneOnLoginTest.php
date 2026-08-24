<?php

use App\Jobs\DetectUserTimezoneJob;
use App\Listeners\UpdateUserTimezoneOnLogin;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Queue;

test('dispatches timezone detection job instead of running it synchronously', function () {
    Queue::fake();

    $user = User::factory()->create();

    request()->server->set('REMOTE_ADDR', '8.8.8.8');

    app(UpdateUserTimezoneOnLogin::class)->handle(new Login('web', $user, false));

    Queue::assertPushed(DetectUserTimezoneJob::class, function ($job) use ($user) {
        return (new ReflectionProperty($job, 'userId'))->getValue($job) === $user->id
            && (new ReflectionProperty($job, 'ip'))->getValue($job) === '8.8.8.8';
    });
});
