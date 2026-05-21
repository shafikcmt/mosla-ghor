<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || Auth::user()->role !== 'customer') {
            return redirect()->route('customer.login')
                ->with('error', 'এই পেজ দেখতে লগইন করুন।');
        }

        return $next($request);
    }
}
