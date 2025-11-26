<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\LogAction;
use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    /**
     * Statistiques globales (Admin)
     * GET /api/statistiques/global
     */
    public function global()
    {
        $stats = [
            'total_documents' => Document::where('statut', 'actif')->count(),
            'total_utilisateurs' => User::where('statut', 'actif')->count(),
            'total_departements' => Departement::count(),
            'volume_stockage_total' => Document::where('statut', 'actif')->sum('file_size'),

            'documents_par_statut' => Document::select('statut', DB::raw('count(*) as total'))
                ->groupBy('statut')
                ->get(),

            'documents_par_departement' => DB::table('documents')
                ->join('departements', 'documents.departement_id', '=', 'departements.id')
                ->where('documents.statut', 'actif')
                ->select('departements.nom_departement', DB::raw('count(*) as total'))
                ->groupBy('departements.nom_departement')
                ->get()
                ->map(function($item) {
                    return [
                        'nom_departement' => $item->nom_departement,
                        'total' => (int) $item->total
                    ];
                })
                ->toArray(),  // ✅ Convertir en tableau

            'documents_par_type' => DB::table('documents')
                ->join('type_documents', 'documents.type_document_id', '=', 'type_documents.id')
                ->where('documents.statut', 'actif')
                ->select('type_documents.libelle_type', DB::raw('count(*) as total'))
                ->groupBy('type_documents.libelle_type')
                ->get()
                ->map(function($item) {
                    return [
                        'libelle_type' => $item->libelle_type,
                        'total' => (int) $item->total
                    ];
                })
                ->toArray(),  // ✅ Convertir en tableau

            'activite_recente' => LogAction::with(['user:id,nom_complet', 'document:id,titre'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),

            'utilisateurs_actifs_7j' => LogAction::where('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id'),

            'top_utilisateurs' => DB::table('log_actions')
                ->join('users', 'log_actions.user_id', '=', 'users.id')
                ->select('users.nom_complet', DB::raw('count(*) as total_actions'))
                ->groupBy('users.id', 'users.nom_complet')
                ->orderBy('total_actions', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Rapport hebdomadaire (Admin)
     * GET /api/statistiques/rapport-hebdomadaire
     */
    public function rapportHebdomadaire()
    {
        $startDate = now()->subDays(7);

        $rapport = [
            'periode' => [
                'debut' => $startDate->format('Y-m-d'),
                'fin' => now()->format('Y-m-d'),
            ],
            'documents_ajoutes' => Document::where('created_at', '>=', $startDate)->count(),
            'documents_modifies' => LogAction::where('type_action', 'modification')
                ->where('created_at', '>=', $startDate)
                ->distinct('document_id')
                ->count('document_id'),
            'documents_telecharges' => LogAction::where('type_action', 'telechargement')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'documents_supprimes' => LogAction::where('type_action', 'suppression')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'utilisateurs_actifs' => LogAction::where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
            'actions_par_type' => LogAction::select('type_action', DB::raw('count(*) as total'))
                ->where('created_at', '>=', $startDate)
                ->groupBy('type_action')
                ->get(),
            'actions_par_jour' => LogAction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($rapport, 200);
    }

    /**
     * Mes statistiques personnelles (Tous les utilisateurs)
     * GET /api/statistiques/mes-stats
     */
    public function mesStats()
    {
        $userId = auth()->id();

        $stats = [
            'mes_documents' => Document::where('user_createur_id', $userId)
                ->where('statut', 'actif')
                ->count(),
            'mes_telechargements' => LogAction::where('user_id', $userId)
                ->where('type_action', 'telechargement')
                ->count(),
            'mes_modifications' => LogAction::where('user_id', $userId)
                ->where('type_action', 'modification')
                ->count(),
            'mes_consultations' => LogAction::where('user_id', $userId)
                ->where('type_action', 'consultation')
                ->count(),
            'mes_actions_recentes' => LogAction::where('user_id', $userId)
                ->with('document:id,titre')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'documents_par_type' => DB::table('documents')
                ->join('type_documents', 'documents.type_document_id', '=', 'type_documents.id')
                ->where('documents.user_createur_id', $userId)
                ->where('documents.statut', 'actif')
                ->select('type_documents.libelle_type', DB::raw('count(*) as total'))
                ->groupBy('type_documents.libelle_type')
                ->get(),
            'activite_7_derniers_jours' => LogAction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total')
            )
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Tableau de bord (personnalisé selon le rôle)
     * GET /api/statistiques/dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->global();
        }

        if ($user->isChef()) {
            $departementController = new DepartementController();
            return $departementController->stats($user->departement_id);
        }

        return $this->mesStats();
    }
}
