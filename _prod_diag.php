<?php
use App\Models\Product;

echo 'total                = ' . Product::count() . PHP_EOL;
echo 'is_active=1          = ' . Product::where('is_active', true)->count() . PHP_EOL;
echo 'active() scope       = ' . Product::active()->count() . PHP_EOL;
echo 'active + retail      = ' . Product::active()->where('show_in_retail', true)->count() . PHP_EOL;
echo 'active + wholesale   = ' . Product::active()->where('show_in_wholesale', true)->count() . PHP_EOL;
echo 'inactive             = ' . Product::where('is_active', false)->count() . PHP_EOL;
echo 'vendor unapproved    = ' . Product::whereNotNull('vendor_id')->where('approval_status', '!=', 'approved')->count() . PHP_EOL;
echo 'retail hidden (show_in_retail=0) = ' . Product::where('show_in_retail', false)->count() . PHP_EOL;
echo '--- active retail products (what homepage retail shows) ---' . PHP_EOL;
foreach (Product::active()->where('show_in_retail', true)->get(['id','name_bn','is_active','show_in_retail','vendor_id','approval_status']) as $p) {
    echo sprintf("#%d  %s  active=%d retail=%d vendor=%s appr=%s\n",
        $p->id, $p->name_bn, $p->is_active, $p->show_in_retail, $p->vendor_id ?? '-', $p->approval_status ?? '-');
}
