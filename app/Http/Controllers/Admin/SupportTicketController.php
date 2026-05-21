<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index()
    {
        $status  = request('status');
        $tickets = SupportTicket::with(['user', 'order'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.support-tickets.index', compact('tickets', 'status'));
    }

    public function show(SupportTicket $supportTicket)
    {
        $supportTicket->load(['user', 'order']);
        return view('admin.support-tickets.show', compact('supportTicket'));
    }

    public function reply(Request $request, SupportTicket $supportTicket)
    {
        $data = $request->validate([
            'admin_reply' => 'required|string|max:2000',
            'status'      => 'required|in:open,replied,closed',
        ], [
            'admin_reply.required' => 'উত্তর লিখুন।',
        ]);

        $supportTicket->update([
            'admin_reply' => $data['admin_reply'],
            'status'      => $data['status'],
            'replied_at'  => now(),
        ]);

        return back()->with('success', 'উত্তর পাঠানো হয়েছে।');
    }
}
