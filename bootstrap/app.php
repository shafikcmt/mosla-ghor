<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         // Trust Cloudflare Tunnel / reverse proxy so HTTPS URLs are generated correctly
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin'         => \App\Http\Middleware\AdminMiddleware::class,
            'vendor'        => \App\Http\Middleware\VendorMiddleware::class,
            'customer-auth' => \App\Http\Middleware\CustomerMiddleware::class,
            'guest.allowed' => \App\Http\Middleware\GuestAllowed::class,
            'bot.token'     => \App\Http\Middleware\AuthenticateBotToken::class,

        ]);
        // Meta's first-party cookies are set by fbevents.js, not Laravel — never try to decrypt them.
        $middleware->encryptCookies(except: ['_fbp', '_fbc']);
        // Admin-controlled maintenance mode (after session/auth so admins can be recognised).
        $middleware->web(append: [\App\Http\Middleware\MaintenanceMode::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['api_key', 'api_secret']);
        // Whole request bigger than PHP's post_max_size (thrown before sessions/controllers run).
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $limit = \App\Support\ServerLimits::human(\App\Support\ServerLimits::postMax());
            \Illuminate\Support\Facades\Log::warning('Request larger than post_max_size', [
                'content_length' => (int) $request->server('CONTENT_LENGTH'),
                'post_max_size'  => ini_get('post_max_size'),
                'path'           => $request->path(),
            ]);
            $message = "একবারে পাঠানো ফাইলগুলো মোট অনেক বড় (সার্ভারের সীমা {$limit})। কয়েকটি ছবি কম দিয়ে আবার চেষ্টা করুন — ছবি আমরা নিজে থেকে ছোট করে দিই।";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'layer' => 'php_post_max_size'], 413);
            }
            return response()->view('errors.413', ['message' => $message], 413);
        });
        // Redirect back with a friendly message on CSRF token expiry (419)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please try again.'], 419);
            }
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation', 'api_key', 'api_secret'))
                ->with('error', 'Session expired. Please try again. (পেজটি refresh করে আবার চেষ্টা করুন।)');
        });
    })->create();
