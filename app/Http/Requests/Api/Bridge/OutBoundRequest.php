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
                    $type = request()->input('body.type');

                    if ($type === 'json') {
                        json_decode($value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $fail('The ' . $attribute . ' must be a valid JSON.');
                        }
                    } elseif ($type === 'xml') {
                        try {
                            new \SimpleXMLElement($value);
                        } catch (\Exception $e) {
                            $fail('The ' . $attribute . ' must be a valid XML.');
                        }
                    }
                },
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 400));
    }
}
