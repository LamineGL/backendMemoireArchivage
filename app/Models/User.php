<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
//    protected $fillable = [
//        'name',
//        'email',
//        'password',
//    ];

    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['photo_url'];


    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    // Accessor pour l'URL de la photo
    public function getPhotoUrlAttribute()
    {
        if ($this->photo_profil) {
            return url('storage/' . $this->photo_profil);
        }
        return null;
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role && $this->role->nom_role === 'Admin';
    }

    public function isChef()
    {
        return $this->role && $this->role->nom_role === 'Chef_Departement';
    }

    public function isEmploye()
    {
        return $this->role && $this->role->nom_role === 'Employe';
    }
}
