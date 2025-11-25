<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserNotBlocked
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (method_exists($user, 'isBlocked') && $user->isBlocked()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['status' => 'Akun Anda diblokir. Silakan hubungi administrator.']);
        }

        return $next($request);
    }
}
