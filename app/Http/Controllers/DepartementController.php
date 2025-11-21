<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartementRequest;
use App\Models\Departement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartementController extends Controller
{
    /**
     * Liste de tous les départements
     * GET /api/departements
     */
    public function index()
    {
        $departements = Departement::withCount(['users', 'documents'])->get();
        return response()->json($departements, 200);
    }

    /**
     * Détails d'un département
     * GET /api/departements/{id}
     */
    public function show($id)
    {
        $departement = Departement::with(['users.role', 'documents'])
            ->withCount(['users', 'documents'])
            ->findOrFail($id);
        return response()->json($departement, 200);
    }

    /**
     * Créer un département (Admin)
     * POST /api/departements
     */
    public function store(DepartementRequest $request)
    {
        $departement = Departement::create($request->validated());

        return response()->json([
            'message' => 'Département créé avec succès',
            'departement' => $departement
        ], 201);
    }

    /**
     * Modifier un département (Admin)
     * PUT /api/departements/{id}
     */
    public function update(DepartementRequest $request, $id)
    {
        $departement = Departement::findOrFail($id);
        $departement->update($request->validated());

        return response()->json([
            'message' => 'Département mis à jour avec succès',
            'departement' => $departement
        ], 200);
    }

    /**
     * Supprimer un département (Admin)
     * DELETE /api/departements/{id}
     */
    public function destroy($id)
    {
        $departement = Departement::findOrFail($id);

        // Vérifier si le département a des utilisateurs
        if ($departement->users()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un département contenant des utilisateurs'
            ], 400);
        }

        // Vérifier si le département a des documents
        if ($departement->documents()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un département contenant des documents'
            ], 400);
        }

        $departement->delete();

        return response()->json([
            'message' => 'Département supprimé avec succès'
        ], 200);
    }

    /**
     * Statistiques d'un département (Chef/Admin)
     * GET /api/departements/{id}/stats
     */
    public function stats($id)
    {
        $user = auth()->user();

        // Vérifier les permissions
        if (!$user->isAdmin() && (!$user->isChef() || $user->departement_id != $id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $departement = Departement::findOrFail($id);

        $stats = [
            'departement' => [
                'id' => $departement->id,
                'nom' => $departement->nom_departement,
                'description' => $departement->description,
            ],
            'total_documents' => $departement->documents()->where('statut', 'actif')->count(),
            'total_employes' => $departement->users()->where('statut', 'actif')->count(),
            'volume_stockage' => $departement->documents()->where('statut', 'actif')->sum('file_size'),
            'documents_par_type' => DB::table('documents')
                ->join('type_documents', 'documents.type_document_id', '=', 'type_documents.id')
                ->where('documents.departement_id', $id)
                ->where('documents.statut', 'actif')
                ->select('type_documents.libelle_type', DB::raw('count(*) as total'))
                ->groupBy('type_documents.libelle_type')
                ->get(),
            'documents_recents' => $departement->documents()
                ->where('statut', 'actif')
                ->with('createur:id,nom_complet')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'titre', 'created_at', 'user_createur_id']),
            'activite_par_employe' => DB::table('log_actions')
                ->join('documents', 'log_actions.document_id', '=', 'documents.id')
                ->join('users', 'log_actions.user_id', '=', 'users.id')
                ->where('documents.departement_id', $id)
                ->select('users.id', 'users.nom_complet', DB::raw('count(*) as total_actions'))
                ->groupBy('users.id', 'users.nom_complet')
                ->orderBy('total_actions', 'desc')
                ->get(),
        ];

        return response()->json($stats, 200);
    }
}
