<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead(string $notification): RedirectResponse
    {
        $record = Auth::user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return redirect($record->data['url'] ?? route('dashboard'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
