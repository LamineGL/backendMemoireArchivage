<?php

namespace App\Services;

use App\Mail\PasswordChangedMail;
use App\Mail\UserCreatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    // ========================================
    // MÉTHODES ADMIN (peut tout modifier)
    // ========================================

    /**
     * Liste des utilisateurs avec filtres
     */
    public function index($filters = [])
    {
        $query = User::with(['role', 'departement']);

        if (isset($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (isset($filters['departement_id'])) {
            $query->where('departement_id', $filters['departement_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nom_complet', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate(20);
    }

    /**
     * Afficher un utilisateur
     */
    public function show($id)
    {
        return User::with(['role', 'departement'])->findOrFail($id);
    }

    /**
     * Créer un utilisateur avec envoi d'email
     */
    public function store(array $data, $photoFile = null)
    {
        $plainPassword = $data['password'] ?? $this->generateRandomPassword();

        if ($photoFile) {
            $photoName = time() . '_' . Str::slug($data['nom_complet']) . '.' . $photoFile->getClientOriginalExtension();
            $photoPath = $photoFile->storeAs('photos_profils', $photoName, 'public');
            $data['photo_profil'] = $photoPath;
        }

        $user = User::create([
            'nom_complet' => $data['nom_complet'],
            'email' => $data['email'],
            'password' => Hash::make($plainPassword),
            'role_id' => $data['role_id'],
            'departement_id' => $data['departement_id'] ?? null,
            'photo_profil' => $data['photo_profil'] ?? null,
            'statut' => $data['statut'] ?? 'actif',
        ]);

        $user->load(['role', 'departement']);
        $this->sendWelcomeEmail($user, $plainPassword);

        return $user;
    }

    /**
     * ✅ Mettre à jour un utilisateur (ADMIN - tous les champs)
     */
    public function update(array $data, $id, $photoFile = null)
    {
        $user = User::findOrFail($id);

        // Upload nouvelle photo
        if ($photoFile) {
            if ($user->photo_profil && Storage::disk('public')->exists($user->photo_profil)) {
                Storage::disk('public')->delete($user->photo_profil);
            }

            $photoName = time() . '_' . Str::slug($data['nom_complet'] ?? $user->nom_complet) . '.' . $photoFile->getClientOriginalExtension();
            $photoPath = $photoFile->storeAs('photos_profils', $photoName, 'public');
            $data['photo_profil'] = $photoPath;
        }

        // Hash du mot de passe si fourni
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user->load(['role', 'departement']);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id, $currentUserId)
    {
        $user = User::findOrFail($id);

        if ($user->id === $currentUserId) {
            throw new \Exception('Vous ne pouvez pas supprimer votre propre compte');
        }

        if ($user->photo_profil && Storage::disk('public')->exists($user->photo_profil)) {
            Storage::disk('public')->delete($user->photo_profil);
        }

        $user->delete();

        return true;
    }

    /**
     * Liste des employés d'un département (pour Chef)
     */
    public function employesDepartement($departementId)
    {
        return User::where('departement_id', $departementId)
            ->with(['role'])
            ->where('statut', 'actif')
            ->where('id', '!=', auth()->id())
            ->get();
    }

    // ========================================
    // MÉTHODES PROFIL PERSONNEL (tous les users)
    // ========================================

    /**
     * ✅ Mettre à jour UNIQUEMENT nom et email
     */
    public function updateOwnInfo($userId, array $data)
    {
        $user = User::findOrFail($userId);

        // 🔒 Seulement nom_complet et email
        $user->update([
            'nom_complet' => $data['nom_complet'],
            'email' => $data['email']
        ]);

        return $user->load(['role', 'departement']);
    }

    /**
     * ✅ Mettre à jour UNIQUEMENT la photo
     */
    public function updateOwnPhoto($userId, $photoFile)
    {
        $user = User::findOrFail($userId);

        // Supprimer l'ancienne photo
        if ($user->photo_profil && Storage::disk('public')->exists($user->photo_profil)) {
            Storage::disk('public')->delete($user->photo_profil);
        }

        // Upload nouvelle photo
        $photoName = time() . '_' . Str::slug($user->nom_complet) . '.' . $photoFile->getClientOriginalExtension();
        $photoPath = $photoFile->storeAs('photos_profils', $photoName, 'public');

        $user->update(['photo_profil' => $photoPath]);

        return $user->load(['role', 'departement']);
    }

    /**
     * Supprimer la photo de profil
     */
    public function deletePhoto($id)
    {
        $user = User::findOrFail($id);

        if ($user->photo_profil) {
            if (Storage::disk('public')->exists($user->photo_profil)) {
                Storage::disk('public')->delete($user->photo_profil);
            }
            $user->photo_profil = null;
            $user->save();
        }

        return $user;
    }

    /**
     * ✅ Changer UNIQUEMENT le mot de passe avec notification email
     */
    public function updateOwnPassword($userId, $currentPassword, $newPassword)
    {
        $user = User::findOrFail($userId);

        // Vérifier l'ancien mot de passe
        if (!Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Le mot de passe actuel est incorrect');
        }

        // Mettre à jour le mot de passe
        $user->password = Hash::make($newPassword);
        $user->save();

        // ✅ Envoyer email de notification
        $this->sendPasswordChangedEmail($user);

        return true;
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * ✅ Envoyer email de bienvenue
     */
    protected function sendWelcomeEmail(User $user, string $password)
    {
        try {
            if (!$user->email || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                \Log::error('Email invalide pour utilisateur', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return;
            }

            Mail::to($user->email)->send(new UserCreatedMail($user, $password));

            \Log::info('Email de bienvenue envoyé avec succès', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur envoi email utilisateur', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * ✅ Envoyer email de confirmation de changement de mot de passe
     */
    protected function sendPasswordChangedEmail(User $user)
    {
        try {
            Mail::to($user->email)->send(new PasswordChangedMail($user));

            \Log::info('Email de changement de mot de passe envoyé', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email changement mot de passe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ Générer un mot de passe aléatoire sécurisé
     */
    private function generateRandomPassword($length = 12)
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%&*';

        $password = '';
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];

        $all = $lowercase . $uppercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * ✅ Renvoyer l'email de bienvenue (si besoin)
     */
    public function resendWelcomeEmail($userId)
    {
        $user = User::with(['role', 'departement'])->findOrFail($userId);
        $newPassword = $this->generateRandomPassword();

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        $this->sendWelcomeEmail($user, $newPassword);

        return $user;
    }
}
