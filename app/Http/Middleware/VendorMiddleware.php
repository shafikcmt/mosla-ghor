<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('vendor.login');
        }

        $user = Auth::user();

        if (! $user->isVendor()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('vendor.login')
                ->with('error', 'এই অ্যাকাউন্টে ভেন্ডর অ্যাক্সেস নেই।');
        }

        // Block admin from accessing vendor panel too
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // Share vendor with all views
        $vendor = $user->vendor;
        view()->share('authVendor', $vendor);

        return $next($request);
    }
}
