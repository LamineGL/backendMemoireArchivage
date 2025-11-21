<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Corbeille extends Model
{
    use HasFactory;

    protected $table = 'corbeille';
    protected $guarded = [];
    public $timestamps = false;

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function suppresseur()
    {
        return $this->belongsTo(User::class, 'supprime_par');
    }
}
