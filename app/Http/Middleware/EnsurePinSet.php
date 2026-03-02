<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canDeleteRecords()) {
            return $next($request);
        }

        if ($user->pin) {
            return $next($request);
        }

        if ($request->routeIs('pin.edit', 'pin.update', 'logout')) {
            return $next($request);
        }

        return redirect()
            ->route('pin.edit')
            ->with('warning', 'Please set your PIN before continuing.');
    }
}
