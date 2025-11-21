<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // ========================================
    // ROUTES ADMIN (peut tout modifier)
    // ========================================

    /**
     * Liste des utilisateurs (Admin)
     * GET /api/users
     */
    public function index(Request $request)
    {
        $filters = $request->only(['role_id', 'departement_id', 'statut', 'search']);
        $users = $this->userService->index($filters);

        return response()->json($users, 200);
    }

    /**
     * Afficher un utilisateur (Admin)
     * GET /api/users/{id}
     */
    public function show($id)
    {
        try {
            $user = $this->userService->show($id);
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Utilisateur introuvable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer un utilisateur (Admin)
     * POST /api/users
     */
    public function store(UserRequest $request)
    {
        try {
            $user = $this->userService->store(
                $request->validated(),
                $request->file('photo_profil')
            );

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Mettre à jour un utilisateur (Admin - TOUS LES CHAMPS)
     * POST|PUT /api/users/{id}
     */
    public function update(UserRequest $request, $id)
    {
        try {
            $user = $this->userService->update(
                $request->validated(),
                $id,
                $request->file('photo_profil')
            );

            return response()->json([
                'message' => 'Utilisateur mis à jour avec succès',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur (Admin)
     * DELETE /api/users/{id}
     */
    public function destroy($id)
    {
        try {
            $this->userService->destroy($id, auth()->id());

            return response()->json([
                'message' => 'Utilisateur supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Vous ne pouvez pas supprimer votre propre compte' ? 403 : 500);
        }
    }

    /**
     * Liste des employés du département (Chef)
     * GET /api/users/departement/employes
     */
    public function employesDepartement()
    {
        $user = auth()->user();
        $employes = $this->userService->employesDepartement($user->departement_id);

        return response()->json($employes, 200);
    }

    // ========================================
    // ROUTES PROFIL PERSONNEL (tous les utilisateurs)
    // ========================================

    /**
     * ✅ Obtenir son propre profil
     * GET /api/profile
     */
    public function getOwnProfile()
    {
        $user = auth()->user()->load(['role', 'departement']);

        return response()->json([
            'user' => $user
        ], 200);
    }

    /**
     * ✅ Mettre à jour ses infos personnelles (nom, email)
     * PUT|POST /api/profile/info
     */
    public function updateOwnInfo(Request $request)
    {
        $request->validate([
            'nom_complet' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ]);

        try {
            $user = $this->userService->updateOwnInfo(
                auth()->id(),
                $request->only(['nom_complet', 'email'])
            );

            return response()->json([
                'message' => 'Informations mises à jour avec succès',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Mettre à jour sa photo de profil
     * POST /api/profile/photo
     */
    public function updateOwnPhoto(Request $request)
    {
        $request->validate([
            'photo_profil' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $this->userService->updateOwnPhoto(
                auth()->id(),
                $request->file('photo_profil')
            );

            return response()->json([
                'message' => 'Photo mise à jour avec succès',
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Supprimer sa photo de profil
     * DELETE /api/profile/photo
     */
    public function deleteOwnPhoto()
    {
        try {
            $user = $this->userService->deletePhoto(auth()->id());

            return response()->json([
                'message' => 'Photo supprimée avec succès',
                'user' => $user->load(['role', 'departement'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Changer son mot de passe
     * PUT /api/profile/password
     */
    public function updateOwnPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->userService->updateOwnPassword(
                auth()->id(),
                $request->current_password,
                $request->password
            );

            return response()->json([
                'message' => 'Mot de passe modifié avec succès. Un email de confirmation vous a été envoyé.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
