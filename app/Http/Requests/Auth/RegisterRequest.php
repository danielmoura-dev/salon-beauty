<?php

namespace App\Http\Requests\Auth;

use App\Models\Affiliate;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'min:2', 'max:100'],
            'business_name'    => ['required', 'string', 'min:2', 'max:150'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'affiliate_code'   => ['nullable', 'string', 'max:30', function ($attr, $value, $fail) {
                if ($value && ! Affiliate::where('code', strtoupper(trim($value)))->where('is_active', true)->exists()) {
                    $fail('Código promocional inválido ou inativo.');
                }
            }],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Informe seu nome.',
            'business_name.required' => 'Informe o nome do estabelecimento.',
            'email.unique'           => 'Este e-mail já está cadastrado.',
            'password.confirmed'     => 'As senhas não conferem.',
            'password.min'           => 'A senha deve ter pelo menos 8 caracteres.',
        ];
    }
}