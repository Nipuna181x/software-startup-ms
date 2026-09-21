<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;

/**
 * Seeds a single demo organization with a Super Admin for local testing.
 *
 * Credentials are read from the environment so no password is ever committed.
 * Set DEMO_ADMIN_EMAIL and DEMO_ADMIN_PASSWORD in your .env file.
 */
class DemoOrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('startsuite.demo.email');
        $password = config('startsuite.demo.password');

        if (blank($password)) {
            $this->command->warn(
                'Skipping demo seed: set DEMO_ADMIN_PASSWORD in your .env file first.',
            );

            return;
        }

        Tenancy::withoutScoping(function () use ($email, $password): void {
            $organization = Organization::firstOrCreate(
                ['slug' => config('startsuite.demo.slug')],
                [
                    'name' => config('startsuite.demo.organization'),
                    'primary_color' => config('startsuite.demo.color'),
                    'is_active' => true,
                ],
            );

            User::updateOrCreate(
                ['email' => $email],
                [
                    'organization_id' => $organization->id,
                    'name' => config('startsuite.demo.name'),
                    'password' => $password,
                    'role' => Role::SuperAdmin,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $this->command->info("Demo organization ready: {$organization->name} ({$email})");
        });
    }
}
