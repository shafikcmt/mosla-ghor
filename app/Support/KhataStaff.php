<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/** Task permissions for shop staff in দোকানের খাতা. The shop owner can do everything. */
class KhataStaff
{
    public const PERMISSIONS = [
        'sale'     => 'বিক্রি লেখা',
        'purchase' => 'ক্রয় লেখা',
        'payment'  => 'টাকা পেলাম / টাকা দিলাম লেখা',
        'expense'  => 'খরচ লেখা',
        'items'    => 'ইনভেন্টরি আইটেম যোগ, সম্পাদনা ও স্টক কম/বেশি',
        'parties'  => 'গ্রাহক / সরবরাহকারী যোগ ও সম্পাদনা',
        'ledger'   => 'লেনদেন, পার্টির বাকি ও স্টেটমেন্ট দেখা',
        'reports'  => 'রিপোর্ট ও মোট পাওনা / বকেয়া / ক্যাশ দেখা',
        'delete'   => 'এন্ট্রি, পার্টি বা আইটেম মুছে ফেলা',
    ];

    /** Pages every staff member may open (item list is needed to sell). */
    private const OPEN = ['vendor.khata.home', 'vendor.khata.items.index', 'vendor.khata.items.show', 'vendor.khata.tx.show',
        'vendor.khata.tx.edit', 'vendor.khata.tx.update']; // tx show/edit check ownership + permission in the controller

    public static function can(?User $user, string $permission): bool
    {
        if (! $user || ! $user->isStaff()) {
            return (bool) $user;
        }

        return in_array($permission, (array) $user->staff_permissions, true);
    }

    /**
     * Permission the current khata route needs: null = open to all staff,
     * 'owner' = shop owner only.
     */
    public static function routePermission(Request $request): ?string
    {
        $route = $request->route();
        $name = $route?->getName() ?? '';
        if (in_array($name, self::OPEN, true)) {
            return null;
        }
        $type = $route?->parameter('type');

        return match ($name) {
            'vendor.khata.trade.create', 'vendor.khata.trade.store' => $type === 'purchase' ? 'purchase' : 'sale',
            'vendor.khata.amount.create', 'vendor.khata.amount.store' => $type === 'expense' ? 'expense' : 'payment',
            'vendor.khata.items.create', 'vendor.khata.items.store', 'vendor.khata.items.edit',
            'vendor.khata.items.update', 'vendor.khata.items.adjust' => 'items',
            'vendor.khata.parties.create', 'vendor.khata.parties.store', 'vendor.khata.parties.edit', 'vendor.khata.parties.update' => 'parties',
            'vendor.khata.parties.index', 'vendor.khata.parties.show', 'vendor.khata.parties.statement', 'vendor.khata.transactions' => 'ledger',
            'vendor.khata.report' => 'reports',
            'vendor.khata.items.destroy', 'vendor.khata.parties.destroy', 'vendor.khata.tx.destroy' => 'delete',
            default => 'owner', // settings, staff management and anything new
        };
    }
}
