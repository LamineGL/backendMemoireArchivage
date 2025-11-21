<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartementRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $deptId = $this->route('id');

        return [
            'nom_departement' => 'required|string|max:100|unique:departements,nom_departement,' . $deptId,
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'nom_departement.required' => 'Le nom du département est obligatoire',
            'nom_departement.unique' => 'Ce nom de département existe déjà',
            'nom_departement.max' => 'Le nom ne doit pas dépasser 100 caractères',
        ];
    }
}
