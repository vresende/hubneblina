<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BridgeForwardingTest extends TestCase
{
    public function test_inbound_forwards_business_headers_without_leaking_hub_authorization(): void
    {
        Http::fake([
            '*' => Http::response('forwarded', 202, ['X-Upstream' => 'yes']),
        ]);

        $response = $this
            ->withoutMiddleware()
            ->withHeaders([
                'Authorization' => 'Bearer hub-token',
                'X-Correlation-ID' => 'correlation-123',
                'X-Business-Unit' => 'sales',
            ])
            ->postJson('/api/bridge/inbound', $this->payload());

        $response
            ->assertStatus(202)
            ->assertHeader('X-Upstream', 'yes');

        $this->assertSame('forwarded', $response->streamedContent());

        /** @var Request $forwardedRequest */
        $forwardedRequest = Http::recorded()->first()[0];

        $this->assertContains('Basic destination-token', $forwardedRequest->header('Authorization'));
        $this->assertSame(['correlation-123'], $forwardedRequest->header('x-correlation-id'));
        $this->assertContains('sales', $forwardedRequest->header('x-business-unit'));
        $this->assertNotContains('Bearer hub-token', $forwardedRequest->header('Authorization'));
        $this->assertNotContains('localhost', $forwardedRequest->header('Host'));
    }

    public function test_outbound_does_not_forward_headers_from_the_hub_request(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $this
            ->withoutMiddleware()
            ->withHeader('X-Correlation-ID', 'must-not-be-forwarded')
            ->postJson('/api/bridge/outbound', $this->payload())
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('X-Correlation-ID'));
    }

    public function test_inbound_get_does_not_require_a_body_or_destination_authorization(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $this
            ->withoutMiddleware()
            ->withHeader('X-Correlation-ID', 'get-123')
            ->postJson('/api/bridge/inbound', [
                'endpoint' => 'https://destination.test/resource?order=123',
                'method' => 'get',
            ])
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->hasHeader('x-correlation-id', 'get-123')
                && ! $request->hasHeader('Authorization');
        });
    }

    private function payload(): array
    {
        return [
            'authorization' => [
                'type' => 'Basic',
                'value' => 'destination-token',
            ],
            'endpoint' => 'https://destination.test/resource',
            'method' => 'post',
            'body' => [
                'type' => 'json',
                'value' => ['order' => 123],
            ],
        ];
    }
}
