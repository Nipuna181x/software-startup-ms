<?php

namespace App\Policies\Calling;

use App\Models\Calling\Business;
use App\Models\User;

/**
 * Authorizes management of businesses within one organization.
 *
 * Any member may view, add and edit businesses; only a Super Admin may
 * delete one. Every check also verifies the business belongs to the actor's
 * organization.
 */
class BusinessPolicy
{
    /**
     * Determine whether the user can view the list of businesses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given business.
     */
    public function view(User $user, Business $business): bool
    {
        return $this->sharesOrganization($user, $business);
    }

    /**
     * Determine whether the user can create businesses.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the given business.
     */
    public function update(User $user, Business $business): bool
    {
        return $this->sharesOrganization($user, $business);
    }

    /**
     * Determine whether the user can delete the given business.
     */
    public function delete(User $user, Business $business): bool
    {
        return $user->isSuperAdmin() && $this->sharesOrganization($user, $business);
    }

    /**
     * Determine whether both the user and the business belong to the same organization.
     */
    private function sharesOrganization(User $user, Business $business): bool
    {
        return $user->organization_id === $business->organization_id;
    }
}
