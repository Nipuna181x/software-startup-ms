<?php

namespace App\Policies\Calling;

use App\Models\Calling\CallBusinessType;
use App\Models\User;

/**
 * Authorizes management of business types within one organization.
 *
 * Any member may view business types; only a Super Admin may create, edit or
 * delete them. Every check also verifies the business type belongs to the
 * actor's organization.
 */
class CallBusinessTypePolicy
{
    /**
     * Determine whether the user can view the list of business types.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given business type.
     */
    public function view(User $user, CallBusinessType $businessType): bool
    {
        return $this->sharesOrganization($user, $businessType);
    }

    /**
     * Determine whether the user can create business types.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the given business type.
     */
    public function update(User $user, CallBusinessType $businessType): bool
    {
        return $user->isSuperAdmin() && $this->sharesOrganization($user, $businessType);
    }

    /**
     * Determine whether the user can delete the given business type.
     *
     * A business type containing businesses cannot be deleted until it is
     * empty.
     */
    public function delete(User $user, CallBusinessType $businessType): bool
    {
        if (! $this->update($user, $businessType)) {
            return false;
        }

        return $businessType->businesses()->doesntExist();
    }

    /**
     * Determine whether both the user and the business type belong to the same organization.
     */
    private function sharesOrganization(User $user, CallBusinessType $businessType): bool
    {
        return $user->organization_id === $businessType->organization_id;
    }
}
