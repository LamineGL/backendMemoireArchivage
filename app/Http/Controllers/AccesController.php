<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccesRequest;
use App\Models\Acces;
use App\Models\Document;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AccesController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Définir les permissions pour un document
     * POST /api/documents/{documentId}/permissions
     */
    public function setPermissions(AccesRequest $request, $documentId)
    {
        $user = auth()->user();
        $document = Document::findOrFail($documentId);
        $validated = $request->validated();

        // ✅ 1. Vérifier les droits sur le DOCUMENT
        if (!$user->isAdmin() &&
            !($user->isChef() && $user->departement_id === $document->departement_id) &&
            $document->user_createur_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé au document'], 403);
        }

        // ✅ 2. Récupérer l'employé cible
        $targetUser = User::findOrFail($validated['user_id']);

        // ✅ 3. Si Chef : vérifier que l'employé est de SON département
        if ($user->isChef() && !$user->isAdmin()) {
            if ($targetUser->departement_id !== $user->departement_id) {
                return response()->json([
                    'message' => 'Vous ne pouvez donner accès qu\'aux employés de votre département'
                ], 403);
            }
        }

        // ✅ 4. Créer/Mettre à jour les permissions
        $acces = Acces::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'document_id' => $documentId
            ],
            [
                'peut_lire' => $validated['peut_lire'] ?? true,
                'peut_telecharger' => $validated['peut_telecharger'] ?? false,
                'peut_modifier' => $validated['peut_modifier'] ?? false,
                'peut_supprimer' => $validated['peut_supprimer'] ?? false,
            ]
        );

        // ✅ 5. NOTIFICATION : Notifier l'employé de ses nouveaux droits
        $permissions = [];
        if ($acces->peut_lire) $permissions[] = 'lecture';
        if ($acces->peut_telecharger) $permissions[] = 'téléchargement';
        if ($acces->peut_modifier) $permissions[] = 'modification';
        if ($acces->peut_supprimer) $permissions[] = 'suppression';

        $permissionsText = implode(', ', $permissions);

        $this->notificationService->notifyUser(
            $targetUser->id,
            $documentId,
            'document_partage',
            'Nouvel accès à un document',
            "Vous avez reçu les droits suivants sur le document '{$document->titre}' : {$permissionsText}.",
            true // ✅ Envoyer email
        );

        return response()->json([
            'message' => 'Permissions définies avec succès',
            'acces' => $acces->load('user:id,nom_complet,email')
        ], 200);
    }

    /**
     * Liste des permissions d'un document
     * GET /api/documents/{documentId}/permissions
     */
    public function getDocumentPermissions($documentId)
    {
        $user = auth()->user();
        $document = Document::findOrFail($documentId);

        // Vérifier les droits
        if (!$user->isAdmin() &&
            !($user->isChef() && $user->departement_id === $document->departement_id) &&
            $document->user_createur_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $permissions = Acces::where('document_id', $documentId)
            ->with('user:id,nom_complet,email,role_id')
            ->get();

        return response()->json($permissions, 200);
    }

    /**
     * Révoquer l'accès d'un utilisateur
     * DELETE /api/documents/{documentId}/permissions/{userId}
     */
    public function revokeAccess($documentId, $userId)
    {
        $user = auth()->user();
        $document = Document::findOrFail($documentId);

        // Vérifier les droits
        if (!$user->isAdmin() &&
            !($user->isChef() && $user->departement_id === $document->departement_id) &&
            $document->user_createur_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $deleted = Acces::where('document_id', $documentId)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Permission introuvable'], 404);
        }

        // ✅ NOTIFICATION : Notifier l'utilisateur de la révocation
        $this->notificationService->notifyUser(
            $userId,
            $documentId,
            'document_partage',
            'Accès révoqué',
            "Votre accès au document '{$document->titre}' a été révoqué.",
            false // Pas d'email pour révocation
        );

        return response()->json([
            'message' => 'Accès révoqué avec succès'
        ], 200);
    }

    /**
     * Mes accès (documents auxquels j'ai accès)
     * GET /api/mes-acces
     */
    public function mesAcces()
    {
        $userId = auth()->id();

        $acces = Acces::where('user_id', $userId)
            ->with(['document.typeDocument', 'document.departement'])
            ->get();

        return response()->json($acces, 200);
    }
}
