<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Services\DocumentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    protected $documentService;
    protected $notificationService;

    public function __construct(
        DocumentService $documentService,
        NotificationService $notificationService
    ) {
        $this->documentService = $documentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des documents
     * GET /api/documents
     */
    public function index(Request $request)
    {
        $filters = $request->only(['departement_id', 'type_document_id', 'search', 'date_debut', 'date_fin']);
        $documents = $this->documentService->index($filters);

        return response()->json($documents, 200);
    }

    /**
     * Créer un document
     * POST /api/documents
     */
    public function store(DocumentRequest $request)
    {
        try {
            $document = $this->documentService->store(
                $request->validated(),
                $request->file('fichier'),
                auth()->id()
            );

            // ✅ NOTIFICATION : Notifier les chefs ET les admins
            $this->notificationService->notifyChefsDepartement(
                $document->departement_id,
                $document->id,
                $document
            );

            return response()->json([
                'message' => 'Document créé avec succès',
                'document' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un document
     * GET /api/documents/{id}
     */
    public function show($id)
    {
        try {
            $document = $this->documentService->show($id, auth()->id());
            return response()->json($document, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Document introuvable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un document
     * PUT/PATCH /api/documents/{id}
     */
    public function update(DocumentRequest $request, $id)
    {
        try {
            $document = $this->documentService->update(
                $request->validated(),
                $id,
                $request->file('fichier'),
                auth()->id()
            );

            // ✅ NOTIFICATION : Notifier les admins de la modification
            $this->notificationService->notifyAdminsAboutDocument(
                $document->id,
                $document,
                'document_modifie'
            );

            // Notifier aussi les utilisateurs ayant accès au document
            $this->notificationService->notifyDocumentUsers(
                $document->id,
                'Document modifié',
                "Le document '{$document->titre}' a été modifié.",
                'document_modifie'
            );

            return response()->json([
                'message' => 'Document mis à jour avec succès',
                'document' => $document,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un document
     * DELETE /api/documents/{id}
     */
    public function destroy($id)
    {
        try {
            // Récupérer le document avant suppression pour la notification
            $document = \App\Models\Document::findOrFail($id);

            $this->documentService->destroy($id, auth()->id());

            // ✅ NOTIFICATION : Notifier les admins de la suppression
            $this->notificationService->notifyAdminsAboutDocument(
                $document->id,
                $document,
                'document_supprime'
            );

            return response()->json([
                'message' => 'Document supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger un document
     * GET /api/documents/{id}/download
     */
    public function download($id)
    {
        try {
            $fileData = $this->documentService->download($id, auth()->id());
            return response()->download($fileData['path'], $fileData['name']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du téléchargement',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Restaurer un document supprimé
     * POST /api/documents/{id}/restore
     */
    public function restore($id)
    {
        try {
            $document = $this->documentService->restore($id, auth()->id());

            // ✅ NOTIFICATION : Notifier les admins de la restauration
            $this->notificationService->notifyAdmin(
                'Document restauré',
                "Le document '{$document->titre}' a été restauré par " . auth()->user()->nom_complet,
                'document_restaure',
                $document->id
            );

            return response()->json([
                'message' => 'Document restauré avec succès',
                'document' => $document
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des versions d'un document
     * GET /api/documents/{id}/versions
     */
    public function versions($id)
    {
        try {
            $versions = $this->documentService->getVersions($id);
            return response()->json($versions, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des versions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
