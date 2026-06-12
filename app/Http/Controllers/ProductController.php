<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\WholesaleEnquiry;
use App\Notifications\EnquiryReceivedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /** Public, SEO-friendly RETAIL product detail page (/products/{slug}). */
    public function show(Request $request, Product $product)
    {
        // Honor an explicit wholesale mode (?mode=wholesale / ?tab=wholesale|paykari)
        // and always show wholesale-only products in the enquiry layout.
        $wholesale = $this->wantsWholesaleMode($request) || $product->isWholesale();
        return $this->renderDetail($product, $wholesale);
    }

    /** True when the request explicitly asks for wholesale/paykari mode. */
    protected function wantsWholesaleMode(Request $request): bool
    {
        $mode = strtolower((string) ($request->query('mode') ?? $request->query('tab') ?? ''));
        return in_array($mode, ['wholesale', 'paykari'], true);
    }

    /** Public WHOLESALE product detail page (/wholesale/products/{slug}) — price hidden, enquiry-only. */
    public function showWholesale(Product $product)
    {
        return $this->renderDetail($product, true);
    }

    /** Shared detail renderer. $wholesaleView forces the price-hidden, enquiry-only layout. */
    protected function renderDetail(Product $product, bool $wholesaleView)
    {
        // Only active products are visible; vendor products must be approved.
        abort_unless(
            $product->is_active
                && (is_null($product->vendor_id) || $product->approval_status === 'approved'),
            404
        );

        $product->load([
            'vendor',
            'category.parent',
            'activeRetailPrices',
            'activeWholesalePrices',
            'activeVariants.activePrices',
        ]);

        $retailPrices    = $product->activeRetailPrices;
        $wholesalePrices = $product->activeWholesalePrices;

        // Related products: same category first, then fall back to the latest.
        // Wholesale view is URL-driven, so any product can be shown as wholesale —
        // we only exclude wholesale-only products from the RETAIL related list.
        $relatedWholesale = $wholesaleView || $product->isWholesale();
        $relatedBase = function () use ($product, $relatedWholesale) {
            $q = Product::active()
                ->where('id', '!=', $product->id)
                ->with(['category', 'activeRetailPrices']);
            if (! $relatedWholesale) {
                $q->where('is_wholesale', false); // retail page: only retail-capable products
            }
            return $q;
        };

        $relatedProducts = $product->category_id
            ? $relatedBase()->where('category_id', $product->category_id)->limit(8)->get()
            : collect();
        if ($relatedProducts->isEmpty()) {
            $relatedProducts = $relatedBase()->latest('id')->limit(8)->get();
        }

        $reviews     = $product->approvedReviews()->get();
        $avgRating   = $product->averageRating();
        $reviewCount = $reviews->count();

        return view('storefront.product-detail', compact(
            'product', 'retailPrices', 'wholesalePrices', 'relatedProducts', 'relatedWholesale',
            'reviews', 'avgRating', 'reviewCount', 'wholesaleView'
        ));
    }

    /** Public wholesale enquiry bag — manage multiple products and submit one enquiry. */
    public function enquiryBag()
    {
        return view('storefront.enquiry-bag');
    }

    /** Public review submission — guest or logged-in. Starts as pending. */
    public function storeReview(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $isCustomer = Auth::check() && Auth::user()->role === 'customer';

        $validated = $request->validate([
            'rating'           => ['required', 'integer', 'min:1', 'max:5'],
            'comment'          => ['required', 'string', 'max:2000'],
            'customer_name'    => [$isCustomer ? 'nullable' : 'required', 'string', 'max:100'],
            'customer_contact' => ['nullable', 'string', 'max:100'],
            'image'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $name = $isCustomer
            ? (Auth::user()->name ?: $request->input('customer_name'))
            : $request->input('customer_name');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path      = $request->file('image')->store('reviews', 'public');
            $imagePath = 'storage/' . $path;
        }

        ProductReview::create([
            'product_id'       => $product->id,
            'user_id'          => $isCustomer ? Auth::id() : null,
            'customer_name'    => $name,
            'customer_contact' => $request->input('customer_contact') ?: null,
            'rating'           => $validated['rating'],
            'comment'          => $validated['comment'],
            'image'            => $imagePath,
            'is_approved'      => false,
        ]);

        return redirect()
            ->to(route('products.show', $product->slug) . '#reviews')
            ->with('success', 'আপনার রিভিউ জমা হয়েছে। অনুমোদনের পর প্রকাশিত হবে। ধন্যবাদ!');
    }

    /**
     * Public wholesale enquiry — guest or logged-in. No forced login.
     * Customer contact stays admin-only (vendors never see phone). Reuses the
     * existing enquiry → quote → chat → admin-approval system unchanged.
     */
    public function storeEnquiry(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $validated = $request->validate([
            'customer_name'     => ['required', 'string', 'max:100'],
            'customer_phone'    => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'delivery_location' => ['required', 'string', 'max:255'],
            'quantity_kg'       => ['required', 'numeric', 'min:0.01'],
            'quantity_unit'     => ['nullable', 'in:kg,gram,bag,carton,piece,packet,ton'],
            'business_type'     => ['nullable', 'in:shop,restaurant,dealer,retailer,other'],
            'customer_whatsapp' => ['nullable', 'string', 'max:20'],
            'message'           => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_phone.regex' => 'সঠিক ফোন নম্বর দিন।',
        ]);

        // Enforce the product's Minimum Order Quantity, if set.
        if ($product->min_order_quantity && (float) $validated['quantity_kg'] < (float) $product->min_order_quantity) {
            $unit = $product->min_order_unit ?: 'kg';
            $qty  = rtrim(rtrim(number_format((float) $product->min_order_quantity, 2, '.', ''), '0'), '.');

            return back()
                ->withInput()
                ->withErrors(['quantity_kg' => "এই পণ্যের জন্য সর্বনিম্ন অর্ডার পরিমাণ {$qty} {$unit}।"]);
        }

        // Link to the customer record when logged in; null for guests.
        $customer = (Auth::check() && Auth::user()->role === 'customer')
            ? Auth::user()->customer
            : null;

        $enquiry = WholesaleEnquiry::create([
            'customer_id'       => $customer?->id,
            'product_id'        => $product->id,
            'vendor_id'         => $product->vendor_id,
            'quantity_kg'       => $validated['quantity_kg'],
            'quantity_unit'     => $validated['quantity_unit'] ?? ($product->min_order_unit ?: 'kg'),
            'delivery_location' => $validated['delivery_location'],
            'business_type'     => $validated['business_type'] ?? 'other',
            'message'           => $validated['message'] ?? null,
            'customer_name'     => $validated['customer_name'],
            'customer_phone'    => $validated['customer_phone'],
            'customer_whatsapp' => $validated['customer_whatsapp'] ?? null,
            'product_name'      => $product->name_bn ?: $product->name_en,
            'status'            => 'pending',
        ]);

        // Alerts: admin + assigned supplier; confirmation to a logged-in customer.
        Notify::admins(new EnquiryReceivedNotification($enquiry, 'admin'));
        Notify::vendor($product->vendor, new EnquiryReceivedNotification($enquiry, 'vendor'));
        Notify::customer($customer, new EnquiryReceivedNotification($enquiry, 'customer'));

        $msg = 'আপনার enquiry successfully submit হয়েছে। MoslaMart team/supplier quote দিয়ে জানাবে।';
        if (! $customer) {
            $msg .= ' আপনি চাইলে পরে enquiry status দেখতে account তৈরি করতে পারেন।';
        }

        // Return to whichever page the enquiry was sent from (wholesale stays on its URL).
        $backRoute = $request->boolean('from_wholesale') ? 'customer.wholesale.products.show' : 'products.show';

        return redirect()
            ->to(route($backRoute, $product->slug) . '#enquiry')
            ->with('success', $msg);
    }
}
