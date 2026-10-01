<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * One page to set খুচরা (retail, per kg) and পাইকারি (wholesale, per kg) prices for
 * many products at once. The Bot API reads the same values for Facebook replies.
 */
class PriceBoardController extends Controller
{
    public function index(Request $request)
    {
        $search = trim(mb_substr((string) $request->query('search', ''), 0, 100));
        $channel = in_array($request->query('channel'), ['retail', 'wholesale'], true) ? $request->query('channel') : '';

        $products = Product::query()
            ->with(['activeRetailPrices.variant'])
            ->withCount('variants')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn ($w) => $w->where('name_bn', 'like', $like)->orWhere('name_en', 'like', $like)
                    ->orWhere('slug', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->when($channel === 'retail', fn ($q) => $q->where('show_in_retail', true))
            ->when($channel === 'wholesale', fn ($q) => $q->where('show_in_wholesale', true))
            ->orderByDesc('is_active')->orderBy('sort_order')->orderBy('id')
            ->paginate(50)->withQueryString();

        return view('admin.price-board', compact('products', 'search', 'channel'));
    }

    public function update(Request $request)
    {
        $rows = (array) $request->input('rows', []);
        $validator = Validator::make(['rows' => $rows], [
            'rows' => 'required|array|max:100',
            'rows.*' => 'array',
            'rows.*.retail_price_1kg' => 'nullable|numeric|min:0.01|max:99999999.99',
            'rows.*.wholesale_price_1kg' => 'nullable|numeric|min:0|max:99999999.99',
        ], [
            'rows.*.retail_price_1kg.numeric' => 'খুচরা দাম একটি সংখ্যা হতে হবে।',
            'rows.*.retail_price_1kg.min' => 'খুচরা দাম ০-এর বেশি হতে হবে।',
            'rows.*.retail_price_1kg.max' => 'খুচরা দাম অনেক বেশি।',
            'rows.*.wholesale_price_1kg.numeric' => 'পাইকারি দাম একটি সংখ্যা হতে হবে।',
            'rows.*.wholesale_price_1kg.min' => 'পাইকারি দাম ০ বা তার বেশি হতে হবে।',
            'rows.*.wholesale_price_1kg.max' => 'পাইকারি দাম অনেক বেশি।',
        ]);
        $validator->after(function ($v) use ($rows) {
            if ($v->errors()->isNotEmpty()) { return; }
            $ids = array_filter(array_keys($rows), fn ($id) => ctype_digit((string) $id));
            if (count($ids) !== count($rows) || Product::whereIn('id', $ids)->count() !== count($ids)) {
                $v->errors()->add('rows', 'পণ্য তালিকা বদলেছে — পেজ রিফ্রেশ করুন।');
            }
            foreach ($rows as $id => $row) {
                if (array_key_exists('retail_price_1kg', $row) && blank($row['retail_price_1kg'])
                    && Product::whereKey($id)->where('show_in_retail', true)->exists()) {
                    $v->errors()->add("rows.$id.retail_price_1kg", 'খুচরা পণ্যের ১ কেজির দাম খালি রাখা যাবে না।');
                }
            }
        });
        $data = $validator->validate()['rows'];

        $changed = DB::transaction(function () use ($data) {
            $changed = [];
            foreach (Product::whereIn('id', array_keys($data))->lockForUpdate()->withCount('variants')->get() as $product) {
                $row = $data[$product->id];
                $wasRetail = (bool) $product->show_in_retail;
                $oldPrice = $product->retail_price_1kg;
                // Variant products price each variant in the full editor.
                if (! $product->variants_count && filled($row['retail_price_1kg'] ?? null)) {
                    $product->retail_price_1kg = $row['retail_price_1kg'];
                }
                if (array_key_exists('wholesale_price_1kg', $row)) {
                    $product->wholesale_price_1kg = blank($row['wholesale_price_1kg']) ? null : $row['wholesale_price_1kg'];
                }
                if (! $product->isDirty()) { continue; }
                $changed[$product->id] = collect($product->getDirty())->map(fn ($new, $key) => ['old' => $product->getOriginal($key), 'new' => $new])->all();
                $product->save();
                if ($product->shouldResyncPrices(false, $wasRetail, $oldPrice)) {
                    $product->syncPrices(); // manual-override packs keep their final_price
                }
            }
            return $changed;
        });

        if ($changed) {
            Log::info('Admin price board update', ['user_id' => $request->user()?->id, 'changes' => $changed]);
        }

        return back()->with('success', $changed ? count($changed).'টি পণ্যের দাম আপডেট হয়েছে।' : 'কোনো পরিবর্তন ছিল না।');
    }
}
