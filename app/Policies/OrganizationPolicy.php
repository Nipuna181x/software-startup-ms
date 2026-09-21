<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can update the organization's settings.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin() && $user->organization_id === $organization->id;
    }
}
