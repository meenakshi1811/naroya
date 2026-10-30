<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $key = $this->rateLimitKey($request);

            // The clinic token screens poll several APIs, so a login gets more room than an anonymous IP.
            return Limit::perMinute(str_starts_with($key, 'ip:') ? 60 : 120)->by($key);
        });
        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(10)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(5)->by($this->rateLimitKey($request));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Who a rate limit counts against. $request->user() is only set on auth:api
     * routes; patient routes (AuthenticateToken) and the public clinic token routes
     * fell back to one bucket per IP, so a doctor and patients on the same Wi-Fi or
     * mobile network blocked each other. Key every call by the logged-in patient or
     * doctor instead, and fall back to the IP only for requests without a valid login.
     */
    protected function rateLimitKey(Request $request): string
    {
        $token = $request->bearerToken();

        if (! $token) {
            return 'ip:' . $request->ip();
        }

        // Patient tokens are encrypted payloads (see AuthenticateToken). Check them
        // first: Passport blanks the Authorization header when it rejects a token.
        try {
            $payload = decrypt($token);

            if (is_array($payload) && ! empty($payload['id'])) {
                return 'patient:' . $payload['id'];
            }
        } catch (DecryptException) {
            // Not a patient token; try a doctor/employee login below.
        }

        $user = Auth::guard('api')->user();

        return $user ? 'user:' . $user->getAuthIdentifier() : 'ip:' . $request->ip();
    }
}
