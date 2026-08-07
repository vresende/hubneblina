<?php

namespace App\Http\Controllers\Api\Bridge;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bridge\OutBoundRequest;
use App\Services\Bridge\HttpForwarder;
use Exception;

class InBoundController extends Controller
{
    public function __invoke(OutBoundRequest $request, HttpForwarder $forwarder)
    {
        try {
            $response = $forwarder->send($request->validated(), $request->headers->all());

            return response()->stream(
                fn () => print ($response->body()),
                $response->status(),
                $response->headers()
            );
        } catch (Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
