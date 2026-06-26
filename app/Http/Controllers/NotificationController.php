<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Gestion des notifications in-app (badge dans la barre de navigation). */
class NotificationController extends Controller
{
    /** Marque une notification comme lue (ou toutes si aucun id). */
    public function markRead(Request $request, ?string $id = null)
    {
        $user = $request->user();

        if ($id) {
            $user->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return back();
    }
}
