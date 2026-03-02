<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): \Illuminate\Http\Response  $next
     * @param  ...$roles  string  The roles to check
     *
     * Example usage in routes:  ->middleware('role:admin,super_admin')
     */
    // public function handle(Request $request, Closure $next, ...$roles)
    // {
    //     // Ensure user is logged in
    //     if (! Auth::check()) {
    //         abort(403, 'Unauthorized.');
    //     }

    //     $user = Auth::user();
    //     // If user’s role is in the list of roles, pass the request along
    //     if (in_array($user->role->name, $roles)) {
    //         return $next($request);
    //     }

    //     // Otherwise, forbid
    //     abort(403, 'You do not have the required role(s).');
    // }

    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        if (! $user || ! $user->roles()->whereIn('name', $roles)->exists()) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }

}
