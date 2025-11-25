<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OAuthController extends Controller
{
    public function __construct(private readonly OAuthService $oauthService)
    {
    }

    public function redirect(): RedirectResponse
    {
        if (! $this->oauthService->isEnabled()) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'SSO tidak tersedia saat ini.']);
        }

        $config = config('services.oauth_server', []);

        // Validate required config
        if (empty($config['uri']) || empty($config['client_id']) || empty($config['redirect'])) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Konfigurasi SSO tidak lengkap.']);
        }

        $queries = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'response_type' => 'code',
        ]);

        return redirect(rtrim($config['uri'], '/').'/oauth/authorize?'.$queries);
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->oauthService->isEnabled()) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'SSO tidak tersedia saat ini.']);
        }

        if (! $request->filled('code')) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Authorization code tidak ditemukan.']);
        }

        $config = config('services.oauth_server', []);

        // Validate required config
        if (empty($config['uri']) || empty($config['client_id']) || empty($config['client_secret'])) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Konfigurasi SSO tidak lengkap.']);
        }

        // Exchange authorization code for access token
        $response = Http::withoutVerifying()->post(rtrim($config['uri'], '/').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect'],
            'code' => $request->code,
        ]);

        $tokenData = $response->json();

        if (! isset($tokenData['access_token'])) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Gagal mendapatkan access token dari SSO.']);
        }

        try {
            // Get user data from SSO server
            $userResponse = Http::withoutVerifying()->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$tokenData['access_token'],
            ])->get(rtrim($config['uri'], '/').'/api/user');

            if ($userResponse->status() !== 200) {
                throw new RuntimeException('Gagal mendapatkan data user dari SSO.');
            }

            $ssoUser = $userResponse->json();

            // Login or register user
            $user = $this->oauthService->loginOrRegisterFromSso($ssoUser, $tokenData);

            if (! $user->profile_completed) {
                return redirect()->route('profile.setup')
                    ->with('info', 'Silakan lengkapi profil Anda terlebih dahulu.');
            }

            return redirect()->route('dashboard');
        } catch (Throwable $exception) {
            Log::error('SSO callback failed', [
                'error' => $exception->getMessage(),
            ]);

            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['oauth' => 'Gagal memproses login SSO.']);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Flush all session data
        $request->session()->flush();
        
        // Logout from auth
        Auth::logout();

        // Delete ALL user sessions from database (logout from all devices)
        if ($user) {
            \DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
            
            // OAuth service logout (revoke tokens, etc) - only for SSO users
            if (!empty($user->sso_id)) {
                $this->oauthService->logout($user);
            }
        }

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Only redirect to SSO logout if user logged in via SSO
        $isSSoUser = $user && !empty($user->sso_id);
        
        if ($isSSoUser) {
            $logoutUri = config('services.oauth_server.uri_logout', null);
            if ($logoutUri) {
                return redirect($logoutUri);
            }
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

}
