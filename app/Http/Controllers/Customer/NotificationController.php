<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(25);

        return view('customer.notifications.index', compact('notifications'));
    }

    public function read(string $id)
    {
        $n = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        $url = $n->data['url'] ?? null;

        return $url ? redirect()->to($url) : redirect()->route('customer.notifications.index');
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'সব নোটিফিকেশন পড়া হয়েছে।');
    }
}
