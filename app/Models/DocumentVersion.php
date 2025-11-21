<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'user_modificateur_id');
    }
}
