<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acces extends Model
{
    use HasFactory;

    protected $table = 'acces';
    protected $guarded = [];

    protected $casts = [
        'peut_lire' => 'boolean',
        'peut_telecharger' => 'boolean',
        'peut_modifier' => 'boolean',
        'peut_supprimer' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

}
