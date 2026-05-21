<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerSupportController extends CustomerBaseController
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', Auth::id())->latest()->paginate(15);
        return view('customer.support.index', compact('tickets'));
    }

    public function create()
    {
        $orders = $this->ordersQuery()->latest()->limit(20)->get();
        return view('customer.support.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:200',
            'message'  => 'required|string|max:2000',
            'order_id' => 'nullable|integer',
        ], [
            'subject.required' => 'বিষয় লিখুন।',
            'message.required' => 'বার্তা লিখুন।',
        ]);

        // Verify the order belongs to this customer if provided
        $orderId = null;
        if (! empty($data['order_id'])) {
            try {
                $order   = $this->findOwnOrder((int) $data['order_id']);
                $orderId = $order->id;
            } catch (\Exception) {
                // ignore invalid order id
            }
        }

        $ticket = SupportTicket::create([
            'user_id'  => Auth::id(),
            'order_id' => $orderId,
            'subject'  => $data['subject'],
            'message'  => $data['message'],
            'status'   => 'open',
        ]);

        return redirect()->route('customer.support.show', $ticket->id)
            ->with('success', 'সাপোর্ট রিকোয়েস্ট পাঠানো হয়েছে।');
    }

    public function show(SupportTicket $supportTicket)
    {
        abort_unless($supportTicket->user_id === Auth::id(), 403);
        return view('customer.support.show', compact('supportTicket'));
    }
}
