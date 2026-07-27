<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolAdmin;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

#[Signature('app:create-school-admin {--name=} {--email=} {--password=} {--school=}')]
#[Description('Create a school admin user')]
class CreateSchoolAdmin extends Command
{
    public function handle()
    {
        $name = $this->option('name') ?? 'Admin User';
        $email = $this->option('email') ?? 'admin@' . (School::first()?->domain ?? 'system.test');
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

            SchoolAdmin::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
            ]);

            $adminRole = Role::where('name', 'Admin')
                ->where('school_id', $school->id)
                ->first();

            if ($adminRole) {
                $user->assignRole($adminRole);
            }

            $this->info("✓ School admin created!");
            $this->info("Email: {$user->email}");
            $this->info("School: {$school->name}");
        } else {
            $this->info("✓ Global admin created!");
            $this->info("Email: {$user->email}");
        }

        return 0;
    }
}
