<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class, 'departement_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'departement_id');
    }


}
