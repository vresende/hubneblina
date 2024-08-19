<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UaribaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autorizar a requisição, você pode alterar conforme sua lógica
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xml|max:10240', // Verifica se o arquivo é XML e tem um tamanho máximo de 10MB
            'usuario' => 'required|string',
            'senha' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'O arquivo XML é obrigatório.',
            'file.file' => 'O arquivo deve ser válido.',
            'file.mimes' => 'O arquivo deve ser do tipo XML.',
            'file.max' => 'O arquivo não pode ser maior que 10MB.',
            'usuario.required' => 'O campo usuário é obrigatório.',
            'senha.required' => 'O campo senha é obrigatório.',
        ];
    }
}
