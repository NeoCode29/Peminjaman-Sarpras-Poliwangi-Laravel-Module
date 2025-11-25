<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function index(): View
    {
        // Check if SSO is enabled from system settings
        $ssoEnabled = \App\Models\SystemSetting::get('enable_sso_login', config('services.oauth_server.sso_enable', false));
        
        return view('auth.login', [
            'ssoEnabled' => filter_var($ssoEnabled, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return $this->index();
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->getCredentials();

        try {
            $result = $this->authService->login(
                $credentials['username'],
                $credentials['password'],
                false
            );
        } catch (AuthenticationException $exception) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => $exception->getMessage()]);
        }

        $request->session()->regenerate();

        if ($result['requires_profile_completion']) {
            return redirect()->route('profile.setup')
                ->with('warning', 'Silakan lengkapi profil Anda sebelum melanjutkan.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Flush all session data
        $request->session()->flush();
        
        // Logout from auth
        Auth::logout();

        // Delete ALL user sessions from database (logout from all devices)
        if ($user) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
            
            // Log audit event
            $this->authService->dispatchLogoutAudit($user);
        }

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Check if user logged in via SSO
        $isSSoUser = $user && !empty($user->sso_id);
        
        // Only redirect to SSO logout if user logged in via SSO
        if ($isSSoUser && config('services.oauth_server.sso_enable', false)) {
            $ssoLogoutUrl = config('services.oauth_server.uri_logout', null);
            if ($ssoLogoutUrl) {
                return redirect($ssoLogoutUrl);
            }
        }

        // For manual login users, redirect to login page
        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
