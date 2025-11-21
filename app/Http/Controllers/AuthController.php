<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom_complet' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'departement_id' => 'required|exists:departements,id',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::create([
            'nom_complet' => $validated['nom_complet'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'departement_id' => $validated['departement_id'],
            'statut' => 'actif',
        ]);

        // Upload photo si fournie
        if ($request->hasFile('photo_profil')) {
            $photoName = time() . '_' . $user->id . '.' . $request->file('photo_profil')->getClientOriginalExtension();
            $photoPath = $request->file('photo_profil')->storeAs('photos_profils', $photoName, 'public');
            $user->photo_profil = $photoPath;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user->load(['role', 'departement']),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Connexion
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations de connexion sont incorrectes.']
            ]);
        }

        if ($user->statut !== 'actif') {
            return response()->json([
                'message' => 'Votre compte est inactif. Contactez un administrateur.'
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user->load(['role', 'departement']),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    }

    /**
     * Profil utilisateur connecté
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['role', 'departement'])
        ], 200);
    }
}
