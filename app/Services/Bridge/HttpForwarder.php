<?php

namespace App\Services\Bridge;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HttpForwarder
{
    private const BLOCKED_HEADERS = [
        'authorization',
        'connection',
        'content-length',
        'content-type',
        'cookie',
        'host',
        'proxy-authorization',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
        'x-forwarded-for',
        'x-forwarded-host',
        'x-forwarded-port',
        'x-forwarded-proto',
    ];

    public function send(array $data, array $forwardedHeaders = []): Response
    {
        $method = strtolower($data['method']);
        $isGet = $method === 'get';
        $headers = $this->filterHeaders($forwardedHeaders);

        if (! empty($data['authorization']['type']) && isset($data['authorization']['value'])) {
            $headers['Authorization'] = trim(
                $data['authorization']['type'].' '.$data['authorization']['value']
            );
        }

        $http = Http::withHeaders($headers);
        $endpoint = $data['endpoint'];

        if ($isGet) {
            return $http->get($endpoint);
        }

        $bodyType = $data['body']['type'] ?? 'json';
        $bodyValue = $data['body']['value'] ?? '';
        $contentType = $bodyType === 'json' ? 'application/json' : 'application/xml';

        if ($bodyType === 'json' && ! is_string($bodyValue)) {
            $bodyValue = json_encode($bodyValue, JSON_THROW_ON_ERROR);
        }

        return $http
            ->withBody((string) $bodyValue, $contentType)
            ->{$method}($endpoint);
    }

    private function filterHeaders(array $headers): array
    {
        $filteredHeaders = array_filter(
            $headers,
            fn (string $name): bool => ! in_array(strtolower($name), self::BLOCKED_HEADERS, true),
            ARRAY_FILTER_USE_KEY
        );

        return array_map(
            fn (mixed $value): mixed => is_array($value) ? implode(', ', $value) : $value,
            $filteredHeaders
        );
    }
}
