<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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

        ];
    }

    public function messages(): array
    {
        return [
            'catalog_file.required' => 'O arquivo XML é obrigatório.',
            'catalog_file.file' => 'O arquivo deve ser válido.',
            'catalog_file.mimes' => 'O arquivo deve ser do tipo XML.',
            'catalog_file.max' => 'O arquivo não pode ser maior que 10MB.',
            'email.required' => 'O campo email é obrigatório.',
            'password.required' => 'O campo password é obrigatório.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Validação falhou',
            'errors' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}
