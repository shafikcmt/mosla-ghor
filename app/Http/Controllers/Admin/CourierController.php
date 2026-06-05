<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourierController extends Controller
{
    public function index()
    {
        $couriers = Courier::orderBy('is_default', 'desc')->orderBy('name')->get();
        return view('admin.couriers.index', compact('couriers'));
    }

    public function create()
    {
        return view('admin.couriers.create');
    }

    public function store(Request $request)
    {
        // Basic courier info only. API credentials/base URL live solely on the
        // Courier API Settings page (single source of truth).
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'slug'           => 'nullable|string|max:100|unique:couriers,slug',
            'status'         => 'required|in:active,inactive',
            'is_default'     => 'boolean',
            'vendor_allowed' => 'boolean',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $data['slug']           = $data['slug'] ?: Str::slug($data['name']);
        $data['is_default']     = $request->boolean('is_default');
        $data['vendor_allowed'] = $request->boolean('vendor_allowed');

        if ($data['is_default']) {
            Courier::where('is_default', true)->update(['is_default' => false]);
        }

        Courier::create($data);

        return redirect()->route('admin.couriers.index')->with('success', 'কুরিয়ার যোগ করা হয়েছে।');
    }

    public function edit(Courier $courier)
    {
        return view('admin.couriers.edit', compact('courier'));
    }

    public function update(Request $request, Courier $courier)
    {
        // Basic courier info only. API credentials/base URL are managed on the
        // Courier API Settings page — never here — so there is nothing for browser
        // autofill to overwrite on this form.
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'slug'           => 'nullable|string|max:100|unique:couriers,slug,' . $courier->id,
            'status'         => 'required|in:active,inactive',
            'is_default'     => 'boolean',
            'vendor_allowed' => 'boolean',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $data['slug']           = $data['slug'] ?: Str::slug($data['name']);
        $data['is_default']     = $request->boolean('is_default');
        $data['vendor_allowed'] = $request->boolean('vendor_allowed');

        if ($data['is_default']) {
            Courier::where('id', '!=', $courier->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $courier->update($data);

        return redirect()->route('admin.couriers.index')->with('success', 'কুরিয়ার আপডেট হয়েছে।');
    }

    public function destroy(Courier $courier)
    {
        $courier->delete();
        return redirect()->route('admin.couriers.index')->with('success', 'কুরিয়ার মুছে ফেলা হয়েছে।');
    }

    public function toggle(Courier $courier)
    {
        $courier->update(['status' => $courier->status === 'active' ? 'inactive' : 'active']);
        return redirect()->route('admin.couriers.index')
            ->with('success', 'কুরিয়ার স্ট্যাটাস পরিবর্তন হয়েছে।');
    }
}
