<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('id') ?? $this->route('user');
        $isUpdate = !is_null($userId);

        $rules = [
            'statut' => 'nullable|in:actif,inactif',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
        ];

        // Si c'est une MODIFICATION
        if ($isUpdate) {
            $rules['nom_complet'] = 'sometimes|required|string|max:100';
            $rules['email'] = 'sometimes|required|email|max:100|unique:users,email,' . $userId;
            $rules['role_id'] = 'sometimes|required|exists:roles,id';
            $rules['departement_id'] = 'sometimes|required|exists:departements,id';
        }
        // Si c'est une CRÉATION
        else {
            $rules['nom_complet'] = 'required|string|max:100';
            $rules['email'] = 'required|email|max:100|unique:users,email';
            $rules['role_id'] = 'required|exists:roles,id';
            $rules['departement_id'] = 'required|exists:departements,id';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'nom_complet.required' => 'Le nom complet est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'role_id.required' => 'Le rôle est obligatoire',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas',
            'departement_id.required' => 'Le département est obligatoire',
            'departement_id.exists' => 'Le département sélectionné n\'existe pas',
            'photo_profil.image' => 'Le fichier doit être une image',
            'photo_profil.mimes' => 'L\'image doit être au format jpeg, png, jpg ou gif',
            'photo_profil.max' => 'L\'image ne doit pas dépasser 2 Mo',
            'statut.in' => 'Le statut doit être "actif" ou "inactif"',
        ];
    }
}
