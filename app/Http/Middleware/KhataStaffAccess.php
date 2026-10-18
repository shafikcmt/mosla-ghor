<?php

namespace App\Http\Middleware;

use App\Support\KhataStaff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks shop staff from khata pages their owner has not allowed. */
class KhataStaffAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isStaff()) {
            $needed = KhataStaff::routePermission($request);
            if ($needed === 'owner' || ($needed !== null && ! KhataStaff::can($user, $needed))) {
                if ($request->isMethod('GET') && ! $request->routeIs('vendor.khata.home')) {
                    return redirect()->route('vendor.khata.home')->with('error', 'এই কাজের অনুমতি আপনার নেই — দোকানের মালিককে বলুন।');
                }
                abort(403, 'এই কাজের অনুমতি আপনার নেই।');
            }
        }

        return $next($request);
    }
}
