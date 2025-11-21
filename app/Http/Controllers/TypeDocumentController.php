<?php

namespace App\Http\Controllers;

use App\Models\TypeDocument;
use Illuminate\Http\Request;

class TypeDocumentController extends Controller
{
    /**
     * Liste de tous les types de documents
     * GET /api/type-documents
     */
    public function index()
    {
        $types = TypeDocument::withCount('documents')->get();
        return response()->json($types, 200);
    }

    /**
     * Créer un type de document (Admin)
     * POST /api/type-documents
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'libelle_type' => 'required|string|max:100|unique:type_documents,libelle_type',
            'description_type' => 'nullable|string',
        ]);

        $type = TypeDocument::create($validated);

        return response()->json([
            'message' => 'Type de document créé avec succès',
            'type' => $type
        ], 201);
    }

    /**
     * Modifier un type de document (Admin)
     * PUT /api/type-documents/{id}
     */
    public function update(Request $request, $id)
    {
        $type = TypeDocument::findOrFail($id);

        $validated = $request->validate([
            'libelle_type' => 'required|string|max:100|unique:type_documents,libelle_type,' . $id,
            'description_type' => 'nullable|string',
        ]);

        $type->update($validated);

        return response()->json([
            'message' => 'Type de document mis à jour avec succès',
            'type' => $type
        ], 200);
    }

    /**
     * Supprimer un type de document (Admin)
     * DELETE /api/type-documents/{id}
     */
    public function destroy($id)
    {
        $type = TypeDocument::findOrFail($id);

        if ($type->documents()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un type ayant des documents associés'
            ], 400);
        }

        $type->delete();

        return response()->json([
            'message' => 'Type de document supprimé avec succès'
        ], 200);
    }
}
