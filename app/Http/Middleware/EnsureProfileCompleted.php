<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $user = $request->user();
        } catch (\Throwable $throwable) {
            return $next($request);
        }

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs([
            'login',
            'login.store',
            'register',
            'register.store',
            'profile.setup',
            'profile.complete',
            'profile.complete-setup',
            'oauth.*',
        ]) || $request->is(['setup*', 'profile/setup*', '/'])) {
            return $next($request);
        }

        try {
            if (method_exists($user, 'isProfileCompleted') && ! $user->isProfileCompleted()) {
                return redirect()->route('profile.setup')
                    ->with('warning', 'Silakan lengkapi profil Anda terlebih dahulu sebelum melanjutkan.');
            }
        } catch (\Throwable $throwable) {
            return $next($request);
        }

        return $next($request);
    }
}
