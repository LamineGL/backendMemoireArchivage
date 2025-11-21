<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'titre' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'type_document_id' => 'required|exists:type_documents,id',
            'departement_id' => 'required|exists:departements,id',
            'mots_cles' => 'nullable|string|max:500',
            'commentaire' => 'nullable|string|max:500',
        ];

        // Pour la création, le fichier est obligatoire
        if ($this->isMethod('post')) {
            $rules['fichier'] = 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip';
        }

        // Pour la mise à jour, le fichier est optionnel
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['fichier'] = 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt,zip';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'titre.required' => 'Le titre du document est obligatoire',
            'titre.max' => 'Le titre ne doit pas dépasser 200 caractères',
            'type_document_id.required' => 'Le type de document est obligatoire',
            'type_document_id.exists' => 'Le type de document sélectionné n\'existe pas',
            'departement_id.required' => 'Le département est obligatoire',
            'departement_id.exists' => 'Le département sélectionné n\'existe pas',
            'fichier.required' => 'Le fichier est obligatoire',
            'fichier.max' => 'Le fichier ne doit pas dépasser 10 Mo',
            'fichier.mimes' => 'Format de fichier non autorisé',
        ];
    }
}
