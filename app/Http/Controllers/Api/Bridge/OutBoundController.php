<?php

    namespace App\Http\Controllers\Api\Bridge;

    use App\Http\Controllers\Controller;
    use App\Http\Requests\Api\Bridge\OutBoundRequest;
    use Exception;
    use Illuminate\Support\Facades\Http;

    class OutBoundController extends Controller
    {
        /**
         * Handle the incoming request.
         */
        public function __invoke(OutBoundRequest $request)
        {
            $data = $request->toArray();

            $method = strtolower($data['method']);
            $isGet = $method === 'get';

// Headers básicos
            $headers = [
                'Authorization' => "{$data['authorization']['type']} {$data['authorization']['value']}",
            ];

// Apenas para métodos com corpo (POST, PUT, etc.)
            if (!$isGet && isset($data['body']['type'])) {
                $headers['Content-Type'] = $data['body']['type'] === 'json'
                    ? 'application/json'
                    : 'application/xml';
            }

            try {
                $http = Http::withHeaders($headers);
                $endpoint = $data['endpoint'];

                if ($isGet) {
// Envia apenas a URL com parâmetros já incluídos
                    $response = $http->get($endpoint);
                } else {
                    $bodyType = $data['body']['type'] ?? 'json';
                    $bodyValue = $data['body']['value'] ?? '';

                    if ($bodyType === 'json') {
                        $rawJson = is_array($bodyValue) || is_object($bodyValue)
                            ? json_encode($bodyValue, JSON_UNESCAPED_UNICODE)
                            : $bodyValue;

                        $response = $http
                            ->withBody($rawJson, 'application/json')
                            ->{$method}($endpoint);
                    } else {
// XML ou outros: envia como string bruta
                        $response = $http
                            ->withBody((string)$bodyValue, 'application/xml')
                            ->{$method}($endpoint);
                    }
                }

                return response()->stream(
                    fn() => print($response->body()),
                    $response->status(),
                    $response->headers()
                );
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }

        }
    }
