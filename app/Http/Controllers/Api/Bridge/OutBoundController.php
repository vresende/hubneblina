<?php

namespace App\Http\Controllers\Api\Bridge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bridge\OutBoundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OutBoundController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(OutBoundRequest $request)
    {
        $data = $request->toArray();
        // Preparar os headers e a requisição
        $headers = [
            'Authorization' => "{$data['authorization']['type']} {$data['authorization']['value']}",
            'Content-Type' => $data['body']['type'] === 'json' ? 'application/json' : 'application/xml',
        ];

        // Fazer a requisição usando a facade Http
        try {
            $response = Http::withHeaders($headers)
                ->{strtolower($data['method'])}($data['endpoint'], $data['body']['value']);

            return response()->json(json_decode($response->body()), $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
