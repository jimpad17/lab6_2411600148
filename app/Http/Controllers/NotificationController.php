<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /** All alerts (stock + approved orders) delivered to the signed-in user. */
    public function index()
    {
        return view('notifications.index', [
            'notifications' => auth()->user()->notifications()->paginate(15),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_type', auth()->user()->getMorphClass())
            ->where('notifiable_id', auth()->id())
            ->firstOrFail();

        $notification->markAsRead();

        return back()->with('success', 'Alert marked as read.');
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        ActivityLogger::log('updated', 'Notifications', 'Marked all notifications as read.');

        return back()->with('success', 'All alerts were marked as read.');
    }
}
