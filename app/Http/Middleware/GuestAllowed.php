<?php

namespace App\Http\Middleware;

use App\Support\AuthSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware `guest.allowed:{checkout|enquiry|review}`. Any logged-in user
 * passes; a guest passes only while the admin toggle for that feature is on.
 * Runs after the web group, so MaintenanceMode always takes priority.
 */
class GuestAllowed
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! AuthSettings::guestBlocked($feature)) {
            return $next($request);
        }

        $message = AuthSettings::GUEST_FEATURES[$feature] ?? 'লগইন করুন';
        // Checkout returns to Review (the cart is in the session); forms return to the page they were on.
        $back = $feature === 'checkout' ? '/checkout/review' : $this->previousPath($request);
        $loginUrl = AuthSettings::loginUrl($back);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message.'।', 'login_url' => $loginUrl], 401);
        }

        return redirect()->to($loginUrl)->with('error', $message.'।');
    }

    private function previousPath(Request $request): string
    {
        $previous = url()->previous();
        $host = parse_url($previous, PHP_URL_HOST);
        if ($host && $host !== $request->getHost()) {
            return '/';
        }
        $path = parse_url($previous, PHP_URL_PATH) ?: '/';
        $query = parse_url($previous, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
    }
}
