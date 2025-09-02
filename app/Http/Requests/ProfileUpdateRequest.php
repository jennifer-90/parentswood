<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        // Autoriser l’utilisateur authentifié à faire cette requête
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            // TOUS en "sometimes" pour que l'absence du champ ne déclenche PAS d'erreur
            'first_name'     => ['sometimes','string','max:255'],
            'last_name'      => ['sometimes','string','max:255'],
            'pseudo' => ['prohibited'], // ce champ ne doit pas être envoyé
            'email'          => ['sometimes','email','max:255','unique:users,email,' . $this->user()->id],
            'picture_profil' => ['nullable','image','mimes:jpeg,png,jpg','max:2048'],
        ];
    }

    public function messages(): array
    {
        // Vous pouvez personnaliser vos messages ici,
        // ou laisser Laravel générer ses défauts si vous n'avez pas besoin de français.
        return [];
    }




}
