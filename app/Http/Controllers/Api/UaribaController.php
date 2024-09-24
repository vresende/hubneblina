<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class UaribaController extends Controller
{
    public function index(Request $request): Application|ResponseFactory|\Illuminate\Foundation\Application|Response
    {
        // Receber o XML do corpo da requisição
        $xmlContent = $request->getContent();

        // Defina as credenciais de autenticação Basic
        $username = 'ARIBA';
        $password = '123456';

        // Enviar o XML para outro endpoint sem verificar o certificado SSL
        $response = Http::withoutVerifying() // Ignorar a verificação SSL
        ->withBasicAuth($username, $password)
            ->withHeaders([
                'Content-Type' => 'application/xml',
            ])
            ->post('http://integracao.grupomater.com.br:883/U_ARIBAADV.APL', $xmlContent);

        // Retornar a resposta do endpoint externo
        return response($response->body(), $response->status())->header('Content-Type', 'application/xml');
    }
}
