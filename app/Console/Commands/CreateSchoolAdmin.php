<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-school-admin {--name=} {--email=} {--password=} {--school=}')]
#[Description('Create a school admin user')]
class CreateSchoolAdmin extends Command
{
    public function handle(SchoolService $schoolService)
    {
        $name = $this->option('name') ?? 'Admin User';
        $firstSchool = School::first();
        $email = $this->option('email') ?? 'admin@'.($firstSchool->domain ?? 'system.test');
        $password = $this->option('password') ?? '123123';

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");

            return 1;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $schoolId = $this->option('school');
        if ($schoolId) {
            $school = School::findOrFail($schoolId);

            $schoolService->attachAdmin($school, $user);

            $this->info('✓ School admin created!');
            $this->info("Email: {$user->email}");
            $this->info("School: {$school->name}");
        } else {
            $this->info('✓ Global admin created!');
            $this->info("Email: {$user->email}");
        }

        return 0;
    }
}
