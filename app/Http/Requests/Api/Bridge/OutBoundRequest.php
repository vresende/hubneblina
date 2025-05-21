<?php

namespace App\Http\Requests\Api\Bridge;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class OutBoundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): true
    {
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
            'authorization' => 'nullable|array',
            'authorization.type' => 'nullable|string',
            'authorization.value' => [
                'nullable',
                'bail',
                function ($attribute, $value, $fail) {
                    if (!is_string($value) && !is_array($value)) {
                        $fail("O campo $attribute deve ser uma string ou um array.");
                    }
                },
            ],
            'endpoint' => 'required|url',
            'method' => 'required|string|in:get,post,put,delete,patch',
			'body' => [
				Rule::requiredIf(fn() => strtolower($this->input('method')) === 'get'),
				'array'
			],
			'body.type' => [
				Rule::requiredIf(fn() => strtolower($this->input('method')) === 'get'),
				'string',
				'in:json,xml'
			],
            'body.value' => [
                Rule::requiredIf(fn() => strtolower($this->input('method')) === 'get'),
                function ($attribute, $value, $fail) {
                    if (!$this->input('body')) {
                        return;
                    }

                    $type = $this->input('body.type');
                    $method = strtolower($this->input('method'));

                    // Se for GET, aceita qualquer valor
                    if ($method === 'get') {
                        return;
                    }

                    if ($type === 'json') {
                        if (is_array($value)) {
                            return;
                        }

                        if (is_string($value)) {
                            // Valida a string JSON
                            json_decode($value);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $fail('O campo ' . $attribute . ' deve ser uma string JSON válida.');
                            }
                        } else {
                            $fail('O campo ' . $attribute . ' deve ser um array ou uma string JSON válida.');
                        }
                    }
                    if ($type === 'xml') {
                        // Valida XML como string
                        if (is_string($value)) {
                            try {
                            } catch (Exception $e) {
                                $fail('O campo ' . $attribute . ' deve ser uma string XML válida.');
                            }
                        } else {
                            $fail('O campo ' . $attribute . ' deve ser uma string XML válida.');
                        }
                    }
                }
            ],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 400));
    }
}
