<?php

namespace App\Http\Requests\Api\Bridge;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OutBoundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Ou implemente sua lógica de autorização
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
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
            'body' => 'required|array',
            'body.type' => 'required|string|in:json,xml',
            'body.value' => [
                'required',
                function ($attribute, $value, $fail) {
                    $type = $this->input('body.type');

                    if ($type === 'json') {
                        if (is_string($value)) {
                            json_decode($value);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $fail('The ' . $attribute . ' must be a valid JSON string.');
                            }
                        } elseif (!is_array($value)) {
                            $fail('The ' . $attribute . ' must be a valid JSON string or array.');
                        }
                    } elseif ($type === 'xml') {
                        if (is_string($value)) {
                            try {
                                new \SimpleXMLElement($value);
                            } catch (\Exception $e) {
                                $fail('The ' . $attribute . ' must be a valid XML string.');
                            }
                        } else {
                            $fail('The ' . $attribute . ' must be a valid XML string.');
                        }
                    }
                },
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
