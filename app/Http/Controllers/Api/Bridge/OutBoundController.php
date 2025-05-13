<?php

	namespace App\Http\Controllers\Api\Bridge;

	use App\Http\Controllers\Controller;
	use App\Http\Requests\Api\Bridge\OutBoundRequest;
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
					$bodyValue = $data['body']['value'] ?? [];

					if ($bodyType === 'json') {
						$response = $http->{$method}($endpoint, $bodyValue);
					} else {
						// XML: envia como string bruta
						$response = $http
							->withBody($bodyValue, 'application/xml')
							->{$method}($endpoint);
					}
				}

				// Tenta interpretar como JSON, senão retorna string bruta
				$decoded = json_decode($response->body(), true);
				$responseBody = json_last_error() === JSON_ERROR_NONE ? $decoded : $response->body();

				return response()->json(['body' => $responseBody], $response->status())
					->withHeaders($response->headers());
			} catch (\Exception $e) {
				return response()->json([
					'success' => false,
					'message' => $e->getMessage()
				], 500);
			}
		}
	}
