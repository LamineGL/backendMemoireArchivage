<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des notifications de l'utilisateur connecté
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $unreadOnly = $request->query('unread_only', false);
        $notifications = $this->notificationService->getUserNotifications(
            auth()->id(),
            $unreadOnly
        );

        return response()->json($notifications, 200);
    }

    /**
     * Nombre de notifications non lues
     * GET /api/notifications/unread-count
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());

        return response()->json(['unread_count' => $count], 200);
    }

    /**
     * Marquer une notification comme lue
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead($id)
    {
        $success = $this->notificationService->markAsRead($id, auth()->id());

        if (!$success) {
            return response()->json(['message' => 'Notification introuvable'], 404);
        }

        return response()->json(['message' => 'Notification marquée comme lue'], 200);
    }

    /**
     * Marquer toutes les notifications comme lues
     * PUT /api/notifications/mark-all-read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(auth()->id());

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ], 200);
    }

    /**
     * Supprimer une notification
     * DELETE /api/notifications/{id}
     */
    public function destroy($id)
    {
        $notification = \App\Models\Notification::where('id', $id)
            ->where('user_destinataire_id', auth()->id())
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification introuvable'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée'], 200);
    }
}
