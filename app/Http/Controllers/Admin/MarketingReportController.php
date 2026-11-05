<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingVisit;
use App\Models\Order;
use App\Models\PaykariComboEnquiry;
use App\Models\WholesaleEnquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Admin → মার্কেটিং রিপোর্ট: visits → orders / enquiries, grouped by utm_source + utm_campaign. */
class MarketingReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = [];

        $key = static function (?string $source, ?string $campaign): string {
            $source = trim((string) $source);
            $campaign = trim((string) $campaign);

            return ($source === '' && $campaign === '') ? '' : $source."\0".$campaign;
        };
        $row = static function (string $k, ?string $source, ?string $campaign) use (&$rows): void {
            $rows[$k] ??= [
                'source' => $k === '' ? null : (trim((string) $source) ?: null),
                'campaign' => $k === '' ? null : (trim((string) $campaign) ?: null),
                'direct' => $k === '',
                'visitors' => 0, 'visits' => 0, 'orders' => 0, 'revenue' => 0.0, 'wholesale' => 0, 'combo' => 0,
            ];
        };

        // 1) visits (one grouped query)
        if (Schema::hasTable('marketing_visits')) {
            $visits = MarketingVisit::query()
                ->selectRaw('utm_source, utm_campaign, count(*) as visits, count(distinct visitor_id) as visitors')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('utm_source', 'utm_campaign')->get();
            foreach ($visits as $v) {
                $k = $key($v->utm_source, $v->utm_campaign);
                $row($k, $v->utm_source, $v->utm_campaign);
                $rows[$k]['visits'] += (int) $v->visits;
                $rows[$k]['visitors'] += (int) $v->visitors;
            }
        }

        // 2) orders: non-cancelled, soft-deleted excluded by the model's SoftDeletes scope
        $orders = Order::query()
            ->selectRaw('utm_source, utm_campaign, count(*) as c, coalesce(sum(grand_total), 0) as revenue')
            ->where('order_status', '!=', 'cancelled')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('utm_source', 'utm_campaign')->get();
        foreach ($orders as $o) {
            $k = $key($o->utm_source, $o->utm_campaign);
            $row($k, $o->utm_source, $o->utm_campaign);
            $rows[$k]['orders'] += (int) $o->c;
            $rows[$k]['revenue'] += (float) $o->revenue;
        }

        // 3) enquiries
        foreach ([['wholesale', WholesaleEnquiry::class], ['combo', PaykariComboEnquiry::class]] as [$field, $model]) {
            $grouped = $model::query()
                ->selectRaw('utm_source, utm_campaign, count(*) as c')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('utm_source', 'utm_campaign')->get();
            foreach ($grouped as $g) {
                $k = $key($g->utm_source, $g->utm_campaign);
                $row($k, $g->utm_source, $g->utm_campaign);
                $rows[$k][$field] += (int) $g->c;
            }
        }

        foreach ($rows as &$r) {
            $conv = $r['orders'] + $r['wholesale'] + $r['combo'];
            $r['conversions'] = $conv;
            $r['rate'] = $r['visitors'] > 0 ? round($conv / $r['visitors'] * 100, 1) : null;
        }
        unset($r);

        // Tagged rows first (most visits, then conversions); the direct/unknown row last.
        $rows = collect($rows)->sort(function ($a, $b) {
            return [$a['direct'], -$a['visits'], -$a['conversions']] <=> [$b['direct'], -$b['visits'], -$b['conversions']];
        })->values();

        $totals = [
            'visits' => $rows->sum('visits'), 'visitors' => $rows->sum('visitors'), 'orders' => $rows->sum('orders'),
            'revenue' => $rows->sum('revenue'), 'wholesale' => $rows->sum('wholesale'), 'combo' => $rows->sum('combo'),
        ];

        return view('admin.marketing-report.index', [
            'rows' => $rows, 'totals' => $totals,
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);
    }

    /** [start-of-day, end-of-day]; default last 30 days; invalid input falls back to the default. */
    private function range(Request $request): array
    {
        try {
            $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
            $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        } catch (\Throwable $e) {
            $to = now()->endOfDay();
            $from = $to->copy()->subDays(29)->startOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
