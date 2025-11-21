<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;


    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'user_destinataire_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
