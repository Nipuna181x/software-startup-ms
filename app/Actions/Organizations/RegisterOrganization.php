<?php

namespace App\Actions\Organizations;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new organization together with its first Super Admin.
 *
 * Both records are written in a single transaction so a failure can never
 * leave an organization without an administrator.
 */
class RegisterOrganization
{
    /**
     * Register the organization and return its first Super Admin.
     *
     * @param  array{name: string, logo_path?: string|null}  $organizationAttributes
     * @param  array{name: string, email: string, password: string}  $adminAttributes
     */
    public function handle(array $organizationAttributes, array $adminAttributes): User
    {
        return DB::transaction(function () use ($organizationAttributes, $adminAttributes): User {
            return Tenancy::withoutScoping(function () use ($organizationAttributes, $adminAttributes): User {
                $organization = Organization::create([
                    'name' => $organizationAttributes['name'],
                    'primary_color' => config('startsuite.brand_color'),
                    'logo_path' => $organizationAttributes['logo_path'] ?? null,
                    'is_active' => true,
                ]);

                return User::create([
                    'organization_id' => $organization->id,
                    'name' => $adminAttributes['name'],
                    'email' => $adminAttributes['email'],
                    'password' => $adminAttributes['password'],
                    'role' => Role::SuperAdmin,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            });
        });
    }
}
