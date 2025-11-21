<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function typeDocument()
    {
        return $this->belongsTo(TypeDocument::class, 'type_document_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'user_createur_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderBy('numero_version', 'desc');
    }

    public function logActions()
    {
        return $this->hasMany(LogAction::class, 'document_id');
    }

    public function acces()
    {
        return $this->hasMany(Acces::class, 'document_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'document_id');
    }
}
