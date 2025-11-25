<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    private const RATE_LIMIT_ATTEMPTS = 3;
    private const RATE_LIMIT_DECAY_SECONDS = 3600; // 60 minutes

    public function __construct(private readonly AuthService $authService)
    {
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Check if manual registration is enabled
        $registrationEnabled = \App\Models\SystemSetting::get('enable_manual_registration', '1');
        
        if (!filter_var($registrationEnabled, FILTER_VALIDATE_BOOLEAN)) {
            return redirect()->route('login')
                ->with('info', 'Registrasi manual saat ini dinonaktifkan. Silakan gunakan SSO untuk login.');
        }

        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $throttleKey = Str::lower('register|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::RATE_LIMIT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan registrasi. Coba lagi dalam ".ceil($seconds / 60).' menit.'])
                ->withInput($request->safe()->except(['password', 'password_confirmation']));
        }

        $data = $request->sanitized();

        $user = $this->authService->register($data);

        RateLimiter::clear($throttleKey);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.setup')
            ->with('success', 'Registrasi berhasil! Silakan lengkapi profil Anda.');
    }
}
