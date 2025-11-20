<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class DebiteurRequest extends FormRequest
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
            'email' => 'required|email',
            'password' => 'required',
            'societe_debitrice' => 'required',
            'gerant' => 'required',
            'ville' => 'required',
            'telephone' => 'required',
        ];

        // Ajouter la validation unique pour la création uniquement
        if ($this->getMethod() == 'POST') {
            $rules['email'][] = function ($attribute, $value, $fail) {
                $exists = \App\Models\Debiteur::where('email', $value)->exists();
                if ($exists) {
                    $fail('⚠️ ALERTE DOUBLON : Un débiteur avec cet email existe déjà dans la base de données !');
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
            'societe_debitrice' => 'société débitrice',
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
        ];
    }
}
