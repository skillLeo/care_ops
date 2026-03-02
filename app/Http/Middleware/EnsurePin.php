<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsurePin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('delete')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->canDeleteRecords()) {
            abort(403, 'You are not authorized to delete records.');
        }

        $pin = (string) $request->input('pin', '');

        if ($pin === '' || ! $user->pin || ! Hash::check($pin, $user->pin)) {
            return back()->withErrors([
                'pin' => 'The provided PIN is invalid.',
            ]);
        }

        return $next($request);
    }
}
