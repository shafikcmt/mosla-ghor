<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index()
    {
        $status  = request('status');
        $returns = ReturnRequest::with(['user', 'order'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.return-requests.index', compact('returns', 'status'));
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['user', 'order.items', 'orderItem']);
        return view('admin.return-requests.show', compact('returnRequest'));
    }

    public function update(Request $request, ReturnRequest $returnRequest)
    {
        $data = $request->validate([
            'status'     => 'required|in:pending,approved,rejected,completed',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $returnRequest->update($data);

        return back()->with('success', 'রিটার্ন রিকোয়েস্ট আপডেট হয়েছে।');
    }
}
