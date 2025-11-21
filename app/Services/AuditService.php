<?php

namespace App\Services;

use App\Models\LogAction;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Enregistrer une action dans les logs
     */
    public function log($userId, $documentId, $typeAction, $details = [])
    {
        return LogAction::create([
            'user_id' => $userId,
            'document_id' => $documentId,
            'type_action' => $typeAction,
//            'adresse_ip' => Request::ip(),
//            'user_agent' => Request::userAgent(),
            'details_action' => $details,
        ]);
    }

    /**
     * Récupérer l'historique complet d'un document
     */
    public function getDocumentHistory($documentId)
    {
        return LogAction::where('document_id', $documentId)
            ->with('user:id,nom_complet,email')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Récupérer les actions d'un utilisateur
     */
    public function getUserActivity($userId, $limit = 50)
    {
        return LogAction::where('user_id', $userId)
            ->with('document:id,titre')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Activité récente globale (pour admin)
     */
    public function getRecentActivity($limit = 20)
    {
        return LogAction::with(['user:id,nom_complet', 'document:id,titre'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Statistiques par type d'action
     */
    public function getActionStats($startDate = null, $endDate = null)
    {
        $query = LogAction::selectRaw('type_action, COUNT(*) as total')
            ->groupBy('type_action');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->get();
    }
}
