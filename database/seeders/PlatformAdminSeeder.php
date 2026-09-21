<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('startsuite.platform_admin.password');

        if (blank($password)) {
            return;
        }

        PlatformAdmin::query()->firstOrCreate(
            ['email' => config('startsuite.platform_admin.email')],
            ['name' => 'Platform Admin', 'password' => $password, 'is_active' => true],
        );
    }
}
