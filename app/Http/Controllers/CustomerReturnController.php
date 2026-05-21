<?php

namespace App\Http\Controllers;

use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerReturnController extends CustomerBaseController
{
    public function index()
    {
        $returns = ReturnRequest::where('user_id', Auth::id())
            ->with('order')
            ->latest()
            ->paginate(15);

        return view('customer.returns.index', compact('returns'));
    }

    public function create(int $orderId)
    {
        $order = $this->findOwnOrder($orderId);

        abort_unless($order->order_status === 'delivered', 403, 'শুধু ডেলিভার্ড অর্ডারের জন্য রিটার্ন করা যায়।');

        $returnWindow = ($order->delivered_at ?? $order->updated_at)->diffInDays(now()) <= 7;
        abort_unless($returnWindow, 403, 'রিটার্নের সময়সীমা শেষ হয়ে গেছে।');

        $existing = ReturnRequest::where('user_id', Auth::id())->where('order_id', $orderId)->first();
        if ($existing) {
            return redirect()->route('customer.returns.show', $existing->id)
                ->with('info', 'এই অর্ডারের জন্য ইতিমধ্যে রিটার্ন রিকোয়েস্ট আছে।');
        }

        $order->load('items');
        return view('customer.returns.create', compact('order'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'      => 'required|integer',
            'order_item_id' => 'nullable|integer|exists:order_items,id',
            'reason'        => 'required|string|max:200',
            'details'       => 'nullable|string|max:1000',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'reason.required' => 'কারণ লিখুন।',
        ]);

        $order = $this->findOwnOrder($data['order_id']);
        abort_unless($order->order_status === 'delivered', 403);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('return-requests', 'public');
        }

        $returnRequest = ReturnRequest::create([
            'user_id'       => Auth::id(),
            'order_id'      => $order->id,
            'order_item_id' => $data['order_item_id'] ?? null,
            'reason'        => $data['reason'],
            'details'       => $data['details'] ?? null,
            'image'         => $imagePath,
            'status'        => 'pending',
        ]);

        return redirect()->route('customer.returns.show', $returnRequest->id)
            ->with('success', 'রিটার্ন রিকোয়েস্ট পাঠানো হয়েছে।');
    }

    public function show(ReturnRequest $returnRequest)
    {
        abort_unless($returnRequest->user_id === Auth::id(), 403);
        $returnRequest->load('order.items', 'orderItem');
        return view('customer.returns.show', compact('returnRequest'));
    }
}
