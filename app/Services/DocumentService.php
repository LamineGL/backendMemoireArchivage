<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    protected $auditService;
    protected $notificationService;

    public function __construct(AuditService $auditService, NotificationService $notificationService)
    {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }

    /**
     * Liste des documents avec filtres
     */
    public function index($filters = [])
    {
        $query = Document::with(['typeDocument', 'departement', 'createur'])
            ->where('statut', 'actif');

        // Filtre par département
        if (isset($filters['departement_id'])) {
            $query->where('departement_id', $filters['departement_id']);
        }

        // Filtre par type de document
        if (isset($filters['type_document_id'])) {
            $query->where('type_document_id', $filters['type_document_id']);
        }

        // Recherche textuelle
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('mots_cles', 'like', "%{$search}%");
            });
        }

        // Filtre par date
        if (isset($filters['date_debut'])) {
            $query->where('created_at', '>=', $filters['date_debut']);
        }

        if (isset($filters['date_fin'])) {
            $query->where('created_at', '<=', $filters['date_fin']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Créer un nouveau document
     */
    public function store(array $data, $fichier, $userId)
    {
        // Upload du fichier
        $fileName = time() . '_' . Str::slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))
            . '.' . $fichier->getClientOriginalExtension();
        $filePath = $fichier->storeAs('documents', $fileName, 'public');

        // Créer le document
        $document = Document::create([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'chemin_fichier' => $filePath,
            'nom_fichier_original' => $fichier->getClientOriginalName(),
            'file_size' => $fichier->getSize(),
            'mime_type' => $fichier->getMimeType(),
            'type_document_id' => $data['type_document_id'],
            'departement_id' => $data['departement_id'],
            'user_createur_id' => $userId,
            'mots_cles' => $data['mots_cles'] ?? null,
            'version' => 1,
            'statut' => 'actif',
        ]);

        // Log de l'action
        $this->auditService->log($userId, $document->id, 'ajout', [
            'titre' => $document->titre,
            'type' => $document->typeDocument->libelle_type ?? null,
            'taille' => $fichier->getSize(),
        ]);

        // Notifier les chefs de département
        $this->notificationService->notifyChefsDepartement(
            $document->departement_id,
            $document->id,
            $document
        );

        return $document->load(['typeDocument', 'departement', 'createur']);
    }

    /**
     * Afficher un document
     */
    public function show($id, $userId)
    {
        $document = Document::with([
            'typeDocument',
            'departement',
            'createur',
            'versions.modificateur'
        ])->findOrFail($id);

        // Log de consultation
        $this->auditService->log($userId, $document->id, 'consultation');

        return $document;
    }

    /**
     * Mettre à jour un document
     */
    public function update(array $data, $id, $fichier = null, $userId)
    {
        $document = Document::findOrFail($id);

        // Si un nouveau fichier est fourni, créer une version
        if ($fichier) {
            // Sauvegarder l'ancienne version
            DocumentVersion::create([
                'document_id' => $document->id,
                'numero_version' => $document->version,
                'chemin_fichier' => $document->chemin_fichier,
                'user_modificateur_id' => $userId,
                'commentaire' => $data['commentaire'] ?? 'Mise à jour du document',
                'file_size' => $document->file_size,
            ]);

            // Upload du nouveau fichier
            $fileName = time() . '_' . Str::slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))
                . '.' . $fichier->getClientOriginalExtension();
            $filePath = $fichier->storeAs('documents', $fileName, 'public');

            // Supprimer l'ancien fichier
            if (Storage::disk('public')->exists($document->chemin_fichier)) {
                Storage::disk('public')->delete($document->chemin_fichier);
            }

            // Mettre à jour les infos du fichier
            $document->chemin_fichier = $filePath;
            $document->nom_fichier_original = $fichier->getClientOriginalName();
            $document->file_size = $fichier->getSize();
            $document->mime_type = $fichier->getMimeType();
            $document->version += 1;
        }

        // Mettre à jour les métadonnées
        $document->titre = $data['titre'] ?? $document->titre;
        $document->description = $data['description'] ?? $document->description;
        $document->type_document_id = $data['type_document_id'] ?? $document->type_document_id;
        $document->departement_id = $data['departement_id'] ?? $document->departement_id;
        $document->mots_cles = $data['mots_cles'] ?? $document->mots_cles;
        $document->save();

        // Log de l'action
        $this->auditService->log($userId, $document->id, 'modification', [
            'titre' => $document->titre,
            'nouvelle_version' => $fichier ? true : false,
            'version' => $document->version,
        ]);

        // Notifier les utilisateurs ayant accès
        $this->notificationService->notifyDocumentUsers(
            $document->id,
            'Document mis à jour',
            "Le document '{$document->titre}' a été modifié."
        );

        return $document->load(['typeDocument', 'departement', 'createur']);
    }

    /**
     * Supprimer un document (soft delete)
     */
    public function destroy($id, $userId)
    {
        $document = Document::findOrFail($id);

        $document->update([
            'statut' => 'supprime',
            'deleted_at' => now(),
        ]);

        // Ajouter à la corbeille
        \App\Models\Corbeille::create([
            'document_id' => $document->id,
            'supprime_par' => $userId,
        ]);

        // Log de l'action
        $this->auditService->log($userId, $document->id, 'suppression', [
            'titre' => $document->titre,
        ]);

        // Notifier les chefs et admins
        $this->notificationService->notifyChefsDepartement(
            $document->departement_id,
            $document->id,
            $document
        );

        return true;
    }

    /**
     * Télécharger un document
     */
    public function download($id, $userId)
    {
        $document = Document::findOrFail($id);

        // Log du téléchargement
        $this->auditService->log($userId, $document->id, 'telechargement', [
            'titre' => $document->titre,
            'taille' => $document->file_size,
        ]);

        $filePath = storage_path('app/public/' . $document->chemin_fichier);

        if (!file_exists($filePath)) {
            throw new \Exception('Fichier introuvable');
        }

        return [
            'path' => $filePath,
            'name' => $document->nom_fichier_original,
        ];
    }

    /**
     * Restaurer un document supprimé
     */
    public function restore($id, $userId)
    {
        $document = Document::findOrFail($id);

        if ($document->statut !== 'supprime') {
            throw new \Exception('Le document n\'est pas dans la corbeille');
        }

        $document->update([
            'statut' => 'actif',
            'deleted_at' => null,
        ]);

        // Supprimer de la corbeille
        \App\Models\Corbeille::where('document_id', $document->id)->delete();

        // Log de restauration
        $this->auditService->log($userId, $document->id, 'restauration', [
            'titre' => $document->titre,
        ]);

        return $document;
    }

    /**
     * Obtenir l'historique des versions
     */
    public function getVersions($documentId)
    {
        return DocumentVersion::where('document_id', $documentId)
            ->with('modificateur:id,nom_complet')
            ->orderBy('numero_version', 'desc')
            ->get();
    }
}
