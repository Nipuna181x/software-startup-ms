<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out any user whose account or organization was deactivated mid-session.
 *
 * Login already rejects inactive accounts; this closes the window where a user
 * is deactivated while they still hold a valid session.
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->canAuthenticate()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => $user->is_active
                        ? __('Your organization has been deactivated. Please contact Startsuite support.')
                        : __('Your account has been deactivated. Please contact your administrator.'),
                ]);
        }

        return $next($request);
    }
}
