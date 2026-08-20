<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class PartenaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $creation = $this->getMethod() == 'POST';

        $rules = [
            'nom' => ['required'],
            'email' => ['required', 'email'],
            // À la modification, un mot de passe vide conserve l'existant.
            'password' => [$creation ? 'required' : 'nullable', 'min:8'],
            'secteur' => ['required'],
            'ville' => ['required'],
            'telephone' => ['required'],
        ];

        // Ajouter la validation unique pour la création uniquement
        if ($creation) {
            $rules['email'][] = function ($attribute, $value, $fail) {
                $exists = \App\Models\Partenaire::where('email', $value)->exists();
                if ($exists) {
                    $fail('⚠️ ALERTE DOUBLON : Un partenaire avec cet email existe déjà dans la base de données !');
                }
            };
        }

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'adresse email',
            'nom' => 'nom du partenaire',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'nom.required' => 'Le nom du partenaire est obligatoire.',
        ];
    }
}
