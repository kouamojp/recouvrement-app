<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class AgentRequest extends FormRequest
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
        $rules = [
            'nom' => 'required',
            'prenom' => 'required',
            'email' => 'required|email',
            'telephone' => 'required',
        ];

        // Ajouter la validation unique pour la création uniquement
        if ($this->getMethod() == 'POST') {
            $rules['email'][] = function ($attribute, $value, $fail) {
                $exists = \App\Models\Agent::where('email', $value)->exists();
                if ($exists) {
                    $fail('⚠️ ALERTE DOUBLON : Un agent avec cet email existe déjà dans la base de données !');
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
            'nom' => 'nom de l\'agent',
            'prenom' => 'prénom de l\'agent',
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
            'nom.required' => 'Le nom de l\'agent est obligatoire.',
            'prenom.required' => 'Le prénom de l\'agent est obligatoire.',
        ];
    }
}
