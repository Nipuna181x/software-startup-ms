<?php

namespace App\Policies\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\Call;
use App\Models\User;

/**
 * Authorizes logging and viewing calls within one organization.
 *
 * Any organization member may log a call against a business in their own
 * organization and view that business's call history. There is no separate
 * update or delete: a call attempt is never edited, only superseded by a
 * new one.
 */
class CallPolicy
{
    /**
     * Determine whether the user can view the call history of a business.
     */
    public function viewAny(User $user, Business $business): bool
    {
        return $user->organization_id === $business->organization_id;
    }

    /**
     * Determine whether the user can view a specific call.
     */
    public function view(User $user, Call $call): bool
    {
        return $user->organization_id === $call->organization_id;
    }

    /**
     * Determine whether the user can log a call against the given business.
     */
    public function create(User $user, Business $business): bool
    {
        return $user->organization_id === $business->organization_id;
    }
}
