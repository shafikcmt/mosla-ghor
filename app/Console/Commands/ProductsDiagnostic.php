<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductsDiagnostic extends Command
{
    /**
     * Usage (run on LIVE to compare against local):
     *   php artisan products:diag
     *
     * Prints the exact counts the storefront uses so you can tell whether a
     * missing product is a DATA issue (inactive / retail disabled / unapproved)
     * or a DB/cache mismatch — the storefront listing itself has NO row limit.
     */
    protected $signature = 'products:diag {--list : Also list each active retail product}';

    protected $description = 'Diagnose why products may not appear on the storefront (data vs cache). Read-only.';

    public function handle(): int
    {
        $this->info('DB connection: ' . config('database.default')
            . ' / database: ' . DB::connection()->getDatabaseName());
        $this->newLine();

        $rows = [
            ['মোট পণ্য (total)',                 Product::count()],
            ['Active (is_active=1)',              Product::where('is_active', true)->count()],
            ['active() scope (frontend base)',    Product::active()->count()],
            ['Active + খুচরা (show_in_retail=1)', Product::active()->where('show_in_retail', true)->count()],
            ['Active + পাইকারি (show_in_wholesale=1)', Product::active()->where('show_in_wholesale', true)->count()],
            ['Inactive (is_active=0)',            Product::where('is_active', false)->count()],
            ['Retail hidden (show_in_retail=0)',  Product::where('show_in_retail', false)->count()],
            ['Vendor unapproved (hidden)',        Product::whereNotNull('vendor_id')->where('approval_status', '!=', 'approved')->count()],
        ];
        $this->table(['Metric', 'Count'], $rows);

        $this->comment('Homepage retail grid shows: "Active + খুচরা" count above (no limit in code).');

        if ($this->option('list')) {
            $this->newLine();
            $this->line('Active retail products:');
            foreach (Product::active()->where('show_in_retail', true)->get(['id', 'name_bn']) as $p) {
                $this->line(sprintf('  #%d  %s', $p->id, $p->name_bn));
            }
        }

        return self::SUCCESS;
    }
}
