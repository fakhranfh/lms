<?php

namespace Tests;

use App\Support\CurrentSchool;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function actingAs($user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user->school_id) {
            $this->app->make(CurrentSchool::class)->setSchoolId($user->school_id);
        }

        return $this;
    }
}
