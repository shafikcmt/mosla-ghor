<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // AJAX/JSON callers (e.g. product quick edit) get a status code, not a login redirect.
        if ($request->expectsJson() && (! Auth::check() || ! Auth::user()->is_admin)) {
            return response()->json(['message' => Auth::check() ? 'এই কাজের অনুমতি নেই।' : 'আবার লগইন করুন।'], Auth::check() ? 403 : 401);
        }

        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        if (! Auth::user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->with('error', 'এই অ্যাকাউন্টে অ্যাডমিন অ্যাক্সেস নেই।');
        }

        return $next($request);
    }
}
