<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user          = Auth::user();
        $notifications = $user->notifications()->paginate(25);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function read(string $id)
    {
        $user = Auth::user();
        $n    = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        $url = $n->data['url'] ?? null;

        return $url ? redirect()->to($url) : redirect()->route('admin.notifications.index');
    }

    public function readAll()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'সব নোটিফিকেশন পড়া হয়েছে।');
    }
}
