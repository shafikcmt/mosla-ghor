<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with('user')->withCount(['products', 'vendorOrders'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('shop_name', 'like', "%$s%")
                  ->orWhere('owner_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        $vendors = $query->paginate(20)->withQueryString();

        $summary = [
            'total'     => Vendor::count(),
            'approved'  => Vendor::where('status', 'approved')->count(),
            'pending'   => Vendor::where('status', 'pending')->count(),
            'suspended' => Vendor::where('status', 'suspended')->count(),
        ];

        $businessTypes = Vendor::BUSINESS_TYPES;

        return view('admin.vendors.index', compact('vendors', 'summary', 'businessTypes'));
    }

    public function create()
    {
        return view('admin.vendors.create', ['businessTypes' => Vendor::BUSINESS_TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_name'        => 'required|string|max:150',
            'owner_name'       => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'email'            => 'nullable|email|max:150',
            'business_type'    => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:500',
            'district'         => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'trade_license'    => 'nullable|string|max:100',
            'nid'              => 'nullable|string|max:50',
            'status'           => 'required|in:pending,approved,suspended',
            'commission_type'  => 'nullable|in:percentage,fixed',
            'commission_value' => 'nullable|numeric|min:0',
            'admin_note'       => 'nullable|string|max:1000',
            'password_mode'    => 'required|in:manual,auto',
            'password'         => 'required_if:password_mode,manual|nullable|string|min:8|confirmed',
        ], [
            'shop_name.required'    => 'ব্যবসা / দোকানের নাম দিন।',
            'owner_name.required'   => 'মালিকের নাম দিন।',
            'phone.required'        => 'ফোন নম্বর দিন।',
            'email.email'           => 'সঠিক ইমেইল দিন।',
            'status.required'       => 'স্ট্যাটাস নির্বাচন করুন।',
            'password.required_if'  => 'ম্যানুয়াল মোডে পাসওয়ার্ড দিন।',
            'password.min'          => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।',
            'password.confirmed'    => 'পাসওয়ার্ড মিলছে না।',
        ]);

        // Duplicate vendor guards (phone always; email when resolvable).
        if (Vendor::where('phone', $data['phone'])->exists()) {
            return back()->withInput()->with('error', 'এই ফোন নম্বর দিয়ে আগেই একটি ভেন্ডর আছে।');
        }

        // Prefer the real email; otherwise generate a safe unique placeholder
        // (vendor login is email-based, so an email must exist).
        $email = ($data['email'] ?? null) ?: $this->placeholderEmail($data['phone']);

        if (Vendor::where('email', $email)->exists()) {
            return back()->withInput()->with('error', 'এই ইমেইল দিয়ে আগেই একটি ভেন্ডর আছে।');
        }

        $plainPassword = null;
        $attached      = false;
        $existingUser  = User::where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->vendor) {
                return back()->withInput()->with('error', 'এই ইমেইল/ইউজারের সাথে আগেই একটি ভেন্ডর যুক্ত আছে।');
            }
            // Attach a vendor profile to the existing (non-vendor) user; keep their password.
            $existingUser->update(['role' => 'vendor']);
            $user     = $existingUser;
            $attached = true;
        } else {
            $plainPassword = $data['password_mode'] === 'auto'
                ? Str::password(12, symbols: false)
                : $data['password'];

            $user = User::create([
                'name'     => $data['owner_name'],
                'email'    => $email,
                'phone'    => $data['phone'],
                'password' => Hash::make($plainPassword),
                'role'     => 'vendor',
                'is_admin' => false,
            ]);
        }

        $status = $data['status'];

        $vendor = Vendor::create([
            'user_id'          => $user->id,
            'shop_name'        => $data['shop_name'],
            'slug'             => $this->uniqueSlug($data['shop_name']),
            'owner_name'       => $data['owner_name'],
            'phone'            => $data['phone'],
            'email'            => $email,
            'address'          => $data['address'] ?? null,
            'district'         => $data['district'] ?? null,
            'city'             => $data['city'] ?? null,
            'trade_license'    => $data['trade_license'] ?? null,
            'nid'              => $data['nid'] ?? null,
            'business_type'    => $data['business_type'] ?? null,
            'commission_type'  => ($data['commission_type'] ?? null) ?: null,
            'commission_value' => ($data['commission_value'] ?? null) ?: null,
            'admin_note'       => $data['admin_note'] ?? null,
            'status'           => $status,
            'is_active'        => ! in_array($status, ['suspended', 'rejected'], true),
            'approved_at'      => $status === 'approved' ? now() : null,
            'approved_by'      => $status === 'approved' ? Auth::id() : null,
            'suspended_at'     => $status === 'suspended' ? now() : null,
        ]);

        $flash = ['success' => 'ভেন্ডর তৈরি হয়েছে' . ($attached ? ' (বিদ্যমান ইউজারে যুক্ত করা হয়েছে)' : '') . '।'];
        if ($plainPassword !== null) {
            // Shown exactly once on the next page; never stored in plain text.
            $flash['generated_password'] = $plainPassword;
            $flash['generated_email']    = $email;
        }

        return redirect()->route('admin.vendors.show', $vendor)->with($flash);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('user', 'pickupPoints');
        $products      = $vendor->products()->orderByDesc('id')->limit(10)->get();
        $combos        = $vendor->combos()->orderByDesc('id')->limit(10)->get();
        $recentOrders  = $vendor->vendorOrders()->with('order')->latest()->limit(10)->get();
        $recentPayouts = $vendor->payouts()->latest()->limit(5)->get();

        return view('admin.vendors.show', compact('vendor', 'products', 'combos', 'recentOrders', 'recentPayouts'));
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', ['vendor' => $vendor, 'businessTypes' => Vendor::BUSINESS_TYPES]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'shop_name'            => 'required|string|max:150',
            'owner_name'           => 'required|string|max:100',
            'phone'                => 'required|string|max:20|unique:vendors,phone,' . $vendor->id,
            'email'                => 'nullable|email|max:150|unique:vendors,email,' . $vendor->id,
            'business_type'        => 'nullable|string|max:100',
            'address'              => 'nullable|string|max:500',
            'district'             => 'nullable|string|max:100',
            'city'                 => 'nullable|string|max:100',
            'trade_license'        => 'nullable|string|max:100',
            'nid'                  => 'nullable|string|max:50',
            'status'               => 'required|in:pending,approved,suspended,rejected',
            'commission_type'      => 'nullable|in:percentage,fixed',
            'commission_value'     => 'nullable|numeric|min:0',
            'product_auto_approve' => 'boolean',
            'admin_note'           => 'nullable|string|max:1000',
        ], [
            'phone.unique' => 'এই ফোন নম্বর অন্য ভেন্ডরে ব্যবহৃত হচ্ছে।',
            'email.unique' => 'এই ইমেইল অন্য ভেন্ডরে ব্যবহৃত হচ্ছে।',
        ]);

        // Keep the linked user account in sync (login email = vendor email).
        $newEmail = $data['email'] ?: $vendor->email;
        if ($vendor->user) {
            if ($newEmail !== $vendor->user->email
                && User::where('email', $newEmail)->where('id', '!=', $vendor->user_id)->exists()) {
                return back()->withInput()->with('error', 'এই ইমেইল দিয়ে আগেই একটি ইউজার অ্যাকাউন্ট আছে।');
            }
            $vendor->user->update([
                'name'  => $data['owner_name'],
                'email' => $newEmail,
                'phone' => $data['phone'],
            ]);
        }

        $status = $data['status'];

        $vendor->update([
            'shop_name'            => $data['shop_name'],
            'owner_name'           => $data['owner_name'],
            'phone'                => $data['phone'],
            'email'                => $newEmail,
            'business_type'        => $data['business_type'] ?: null,
            'address'              => $data['address'] ?: null,
            'district'             => $data['district'] ?: null,
            'city'                 => $data['city'] ?: null,
            'trade_license'        => $data['trade_license'] ?: null,
            'nid'                  => $data['nid'] ?: null,
            'status'               => $status,
            'is_active'            => ! in_array($status, ['suspended', 'rejected'], true),
            'commission_type'      => $data['commission_type'] ?: null,
            'commission_value'     => $data['commission_value'] ?: null,
            'product_auto_approve' => $request->boolean('product_auto_approve'),
            'admin_note'           => $data['admin_note'] ?: null,
            'approved_at'          => $status === 'approved'  ? ($vendor->approved_at ?? now()) : $vendor->approved_at,
            'suspended_at'         => $status === 'suspended' ? now() : $vendor->suspended_at,
        ]);

        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'ভেন্ডর তথ্য আপডেট হয়েছে।');
    }

    public function resetPassword(Vendor $vendor)
    {
        if (! $vendor->user) {
            return back()->with('error', 'এই ভেন্ডরের কোনো লগইন অ্যাকাউন্ট নেই।');
        }

        $plain = Str::password(12, symbols: false);
        $vendor->user->update(['password' => Hash::make($plain)]);

        return redirect()->route('admin.vendors.show', $vendor)->with([
            'success'            => 'নতুন পাসওয়ার্ড তৈরি হয়েছে। নিচের পাসওয়ার্ডটি ভেন্ডরকে দিন (একবারই দেখানো হবে)।',
            'generated_password' => $plain,
            'generated_email'    => $vendor->email,
        ]);
    }

    public function approve(Vendor $vendor)
    {
        $vendor->update([
            'status'      => 'approved',
            'is_active'   => true,
            'approved_at' => $vendor->approved_at ?? now(),
            'approved_by' => Auth::id(),
            'suspended_at'=> null,
        ]);
        $vendor->products()->where('approval_status', 'approved')->update(['is_active' => true]);

        return back()->with('success', 'ভেন্ডর অনুমোদন করা হয়েছে।');
    }

    public function reject(Vendor $vendor)
    {
        $vendor->update(['status' => 'rejected', 'is_active' => false]);
        $vendor->products()->update(['is_active' => false]);

        return back()->with('success', 'ভেন্ডর প্রত্যাখ্যান করা হয়েছে।');
    }

    public function suspend(Vendor $vendor)
    {
        $vendor->update(['status' => 'suspended', 'is_active' => false, 'suspended_at' => now()]);
        $vendor->products()->update(['is_active' => false]);

        return back()->with('success', 'ভেন্ডর স্থগিত করা হয়েছে।');
    }

    public function reactivate(Vendor $vendor)
    {
        $vendor->update([
            'status'       => 'approved',
            'is_active'    => true,
            'approved_at'  => $vendor->approved_at ?? now(),
            'approved_by'  => Auth::id(),
            'suspended_at' => null,
        ]);
        $vendor->products()->where('approval_status', 'approved')->update(['is_active' => true]);

        return back()->with('success', 'ভেন্ডর পুনরায় সক্রিয় করা হয়েছে।');
    }

    public function settings()
    {
        $settings = [
            'vendor_registration_enabled' => WebsiteSetting::get('vendor_registration_enabled', '1'),
            'vendor_auto_approve'          => WebsiteSetting::get('vendor_auto_approve', '0'),
            'vendor_product_auto_approve'  => WebsiteSetting::get('vendor_product_auto_approve', '0'),
            'default_commission_type'      => WebsiteSetting::get('default_commission_type', 'percentage'),
            'default_commission_value'     => WebsiteSetting::get('default_commission_value', '0'),
        ];

        return view('admin.vendors.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'default_commission_type'  => 'required|in:percentage,fixed',
            'default_commission_value' => 'required|numeric|min:0',
        ]);

        $keys = [
            'vendor_registration_enabled',
            'vendor_auto_approve',
            'vendor_product_auto_approve',
            'default_commission_type',
            'default_commission_value',
        ];

        foreach ($keys as $key) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $request->input($key, '0')]);
        }

        return back()->with('success', 'মাল্টিভেন্ডর সেটিং আপডেট হয়েছে।');
    }

    /** Unique vendor slug from a shop name. */
    private function uniqueSlug(string $shopName): string
    {
        $base = Str::slug($shopName) ?: 'vendor';
        $slug = $base;
        $i = 1;
        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Safe unique placeholder email when the admin leaves email blank. */
    private function placeholderEmail(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?: Str::random(8);
        $base   = 'vendor_' . $digits;
        $email  = $base . '@mosla.local';
        $i = 1;
        while (User::where('email', $email)->exists() || Vendor::where('email', $email)->exists()) {
            $email = $base . '-' . $i++ . '@mosla.local';
        }
        return $email;
    }
}
