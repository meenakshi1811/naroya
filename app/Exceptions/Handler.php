<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Sentry\Laravel\Integration;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Passport's token guard reports this every time a request carries an expired,
     * revoked or non-Passport bearer token, then answers 401 as normal. Those are
     * expected, so keep them out of the logs and Sentry. Login (grant) failures use
     * Laravel\Passport\Exceptions\OAuthServerException and are still reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        \League\OAuth2\Server\Exception\OAuthServerException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }
}
