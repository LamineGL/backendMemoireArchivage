<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'peut_lire' => 'boolean',
            'peut_telecharger' => 'boolean',
            'peut_modifier' => 'boolean',
            'peut_supprimer' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'L\'utilisateur est obligatoire',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas',
        ];
    }
}
