<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Acces;

class CheckDocumentAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Récupérer l'ID du document depuis la route
        $documentId = $request->route('id') ?? $request->route('document');

        if (!$documentId) {
            return response()->json(['message' => 'Document non spécifié'], 400);
        }

        // Admin a accès à tout
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Récupérer le document
        $document = Document::find($documentId);

        if (!$document) {
            return response()->json(['message' => 'Document introuvable'], 404);
        }

        // Vérifier si le document est supprimé
        if ($document->statut === 'supprime') {
            return response()->json(['message' => 'Document supprimé'], 404);
        }

        // Chef de département a accès aux documents de son département
        if ($user->isChef() && $user->departement_id === $document->departement_id) {
            return $next($request);
        }

        // Créateur du document a tous les droits
        if ($document->user_createur_id === $user->id) {
            return $next($request);
        }

        // Vérifier les permissions spécifiques
        $acces = Acces::where('user_id', $user->id)
            ->where('document_id', $documentId)
            ->first();

        if (!$acces) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à ce document.'
            ], 403);
        }

        // Vérifier selon le type d'action
        $method = $request->method();
        $action = $request->route()->getActionMethod();

        // Lecture / Consultation
        if ($method === 'GET' && in_array($action, ['show', 'index'])) {
            if ($acces->peut_lire) {
                return $next($request);
            }
        }

        // Téléchargement
        if ($method === 'GET' && $action === 'download') {
            if ($acces->peut_telecharger) {
                return $next($request);
            }
        }

        // Modification
        if (in_array($method, ['PUT', 'PATCH']) || $action === 'update') {
            if ($acces->peut_modifier) {
                return $next($request);
            }
        }

        // Suppression
        if ($method === 'DELETE' || $action === 'destroy') {
            if ($acces->peut_supprimer) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Vous n\'avez pas les permissions nécessaires pour cette action.'
        ], 403);
    }
}
