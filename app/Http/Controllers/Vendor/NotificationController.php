<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user          = Auth::user();
        $notifications = $user->notifications()->paginate(25);

        return view('vendor.notifications.index', compact('notifications'));
    }

    /** Mark one read, then bounce to its target url (or the list). */
    public function read(string $id)
    {
        $user = Auth::user();
        $n    = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        $url = $n->data['url'] ?? null;

        return $url ? redirect()->to($url) : redirect()->route('vendor.notifications.index');
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'সব নোটিফিকেশন পড়া হয়েছে।');
    }
}
