<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Mail\DocumentAddedMail;
use App\Mail\AccessGrantedMail;
use App\Mail\WeeklyReportMail;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Créer une notification
     */
    public function create($userId, $documentId, $type, $titre, $message)
    {
        return Notification::create([
            'user_destinataire_id' => $userId,
            'document_id' => $documentId,
            'type_notification' => $type,
            'titre' => $titre,
            'message' => $message,
            'statut' => 'non_lu',
        ]);
    }

    /**
     * Notifier les chefs de département ET les admins
     */
    public function notifyChefsDepartement($departementId, $documentId, $document)
    {
        // 1. Notifier les chefs de département
        $chefs = User::where('departement_id', $departementId)
            ->whereHas('role', function($query) {
                $query->where('nom_role', 'Chef_Departement');
            })
            ->where('statut', 'actif')
            ->get();

        foreach ($chefs as $chef) {
            $this->create(
                $chef->id,
                $documentId,
                'document_ajoute',
                'Nouveau document ajouté',
                "Un nouveau document '{$document->titre}' a été ajouté au département {$document->departement->nom_departement}."
            );

            try {
                Mail::to($chef->email)->send(new DocumentAddedMail($document, $chef));
            } catch (\Exception $e) {
                \Log::error('Erreur envoi email chef: ' . $e->getMessage());
            }
        }

        // 2. NOUVEAU : Notifier TOUS les admins
        $this->notifyAdminsAboutDocument($documentId, $document, 'document_ajoute');
    }

    /**
     * NOUVELLE MÉTHODE : Notifier les admins pour un document
     */
    public function notifyAdminsAboutDocument($documentId, $document, $type = 'document_ajoute')
    {
        $admins = User::whereHas('role', function($query) {
            $query->where('nom_role', 'Admin');
        })->where('statut', 'actif')->get();

        $messages = [
            'document_ajoute' => "Un nouveau document '{$document->titre}' a été ajouté au département {$document->departement->nom_departement}.",
            'document_modifie' => "Le document '{$document->titre}' a été modifié.",
            'document_supprime' => "Le document '{$document->titre}' a été supprimé.",
        ];

        $titres = [
            'document_ajoute' => 'Nouveau document ajouté',
            'document_modifie' => 'Document modifié',
            'document_supprime' => 'Document supprimé',
        ];

        foreach ($admins as $admin) {
            $this->create(
                $admin->id,
                $documentId,
                $type,
                $titres[$type] ?? 'Notification document',
                $messages[$type] ?? "Action sur le document '{$document->titre}'."
            );

            // Envoyer email seulement pour les nouveaux documents
            if ($type === 'document_ajoute') {
                try {
                    Mail::to($admin->email)->send(new DocumentAddedMail($document, $admin));
                } catch (\Exception $e) {
                    \Log::error('Erreur envoi email admin: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Notifier tous les administrateurs (méthode générique)
     */
    public function notifyAdmin($titre, $message, $type = 'systeme', $documentId = null)
    {
        $admins = User::whereHas('role', function($query) {
            $query->where('nom_role', 'Admin');
        })->where('statut', 'actif')->get();

        foreach ($admins as $admin) {
            $this->create($admin->id, $documentId, $type, $titre, $message);
        }
    }

    /**
     * Notifier un utilisateur spécifique
     */
    public function notifyUser($userId, $documentId, $type, $titre, $message, $sendEmail = false)
    {
        $notification = $this->create($userId, $documentId, $type, $titre, $message);

        if ($sendEmail) {
            $user = User::find($userId);
            $document = \App\Models\Document::find($documentId);

            if ($user && $document) {
                try {
                    Mail::to($user->email)->send(new AccessGrantedMail($document, $user));
                } catch (\Exception $e) {
                    \Log::error('Erreur envoi email: ' . $e->getMessage());
                }
            }
        }

        return $notification;
    }

    /**
     * Notifier les utilisateurs ayant accès à un document
     */
    public function notifyDocumentUsers($documentId, $titre, $message, $type = 'document_modifie')
    {
        $usersWithAccess = \App\Models\Acces::where('document_id', $documentId)
            ->where('peut_lire', true)
            ->pluck('user_id');

        foreach ($usersWithAccess as $userId) {
            $this->create($userId, $documentId, $type, $titre, $message);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_destinataire_id', $userId)
            ->first();

        if ($notification) {
            $notification->update([
                'statut' => 'lu',
                'read_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead($userId)
    {
        return Notification::where('user_destinataire_id', $userId)
            ->where('statut', 'non_lu')
            ->update([
                'statut' => 'lu',
                'read_at' => now()
            ]);
    }

    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications($userId, $unreadOnly = false)
    {
        $query = Notification::where('user_destinataire_id', $userId)
            ->with('document:id,titre');

        if ($unreadOnly) {
            $query->where('statut', 'non_lu');
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount($userId)
    {
        return Notification::where('user_destinataire_id', $userId)
            ->where('statut', 'non_lu')
            ->count();
    }

    /**
     * Envoyer le rapport hebdomadaire aux admins
     */
    public function sendWeeklyReport()
    {
        $admins = User::whereHas('role', function($query) {
            $query->where('nom_role', 'Admin');
        })->where('statut', 'actif')->get();

        $startDate = now()->subDays(7);

        $stats = [
            'documents_ajoutes' => \App\Models\Document::where('created_at', '>=', $startDate)->count(),
            'documents_modifies' => \App\Models\LogAction::where('type_action', 'modification')
                ->where('created_at', '>=', $startDate)
                ->distinct('document_id')
                ->count(),
            'telechargements' => \App\Models\LogAction::where('type_action', 'telechargement')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'utilisateurs_actifs' => \App\Models\LogAction::where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
        ];

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(new WeeklyReportMail($stats, $admin));
            } catch (\Exception $e) {
                \Log::error('Erreur envoi rapport hebdomadaire: ' . $e->getMessage());
            }
        }
    }
}
