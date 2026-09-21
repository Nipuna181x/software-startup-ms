<?php

namespace App\Policies\Calling;

use App\Models\Calling\CallCategory;
use App\Models\User;

/**
 * Authorizes management of call categories within one organization.
 *
 * Any member may view categories; only a Super Admin may create, edit or
 * delete them. Every check also verifies the category belongs to the actor's
 * organization, so a guessed id from another tenant is refused even if the
 * global scope were ever bypassed.
 */
class CallCategoryPolicy
{
    /**
     * Determine whether the user can view the list of categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the given category.
     */
    public function view(User $user, CallCategory $category): bool
    {
        return $this->sharesOrganization($user, $category);
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the given category.
     */
    public function update(User $user, CallCategory $category): bool
    {
        return $user->isSuperAdmin() && $this->sharesOrganization($user, $category);
    }

    /**
     * Determine whether the user can delete the given category.
     *
     * A category containing business types cannot be deleted until it is
     * empty, so accidentally removing everything inside it is impossible.
     */
    public function delete(User $user, CallCategory $category): bool
    {
        if (! $this->update($user, $category)) {
            return false;
        }

        return $category->businessTypes()->doesntExist();
    }

    /**
     * Determine whether both the user and the category belong to the same organization.
     */
    private function sharesOrganization(User $user, CallCategory $category): bool
    {
        return $user->organization_id === $category->organization_id;
    }
}
