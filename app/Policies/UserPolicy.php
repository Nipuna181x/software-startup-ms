<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Authorizes team management inside a single organization.
 *
 * Every check verifies both the actor's role and that the target user belongs
 * to the same organization, so guessing an id from another tenant is denied
 * even if the global scope were ever bypassed.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view the team list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the given team member.
     */
    public function view(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $this->sharesOrganization($user, $target);
    }

    /**
     * Determine whether the user can add team members.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the given team member.
     */
    public function update(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $this->sharesOrganization($user, $target);
    }

    /**
     * Determine whether the user can delete the given team member.
     *
     * A Super Admin may not delete their own account, and the last remaining
     * Super Admin of an organization can never be removed.
     */
    public function delete(User $user, User $target): bool
    {
        if (! $this->update($user, $target)) {
            return false;
        }

        if ($user->is($target)) {
            return false;
        }

        return ! $this->isLastSuperAdmin($target);
    }

    /**
     * Determine whether the user can deactivate the given team member.
     */
    public function deactivate(User $user, User $target): bool
    {
        return $this->delete($user, $target);
    }

    /**
     * Determine whether the user can change the role of the given member.
     *
     * Demoting the last Super Admin would leave the organization unmanageable.
     */
    public function changeRole(User $user, User $target, Role $newRole): bool
    {
        if (! $this->update($user, $target)) {
            return false;
        }

        if ($newRole === Role::SuperAdmin) {
            return true;
        }

        return ! $this->isLastSuperAdmin($target);
    }

    /**
     * Determine whether the user can reset the given member's password.
     */
    public function resetPassword(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    /**
     * Determine whether the given user is the last active Super Admin.
     */
    public function isLastSuperAdmin(User $target): bool
    {
        if (! $target->isSuperAdmin()) {
            return false;
        }

        return User::query()
            ->where('organization_id', $target->organization_id)
            ->where('role', Role::SuperAdmin)
            ->whereKeyNot($target->getKey())
            ->doesntExist();
    }

    /**
     * Determine whether both users belong to the same organization.
     */
    private function sharesOrganization(User $user, User $target): bool
    {
        return $user->organization_id === $target->organization_id;
    }
}
