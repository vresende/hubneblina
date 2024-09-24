<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UaribaController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->email !== 'uariba@grupomater.com.br') {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
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
            ->send('POST', 'http://integracao.grupomater.com.br:883/U_ARIBAADV.APL', [
                'body' => $xmlContent, // Enviar o conteúdo bruto do XML
            ]);

        // Retornar a resposta do endpoint externo
        return response($response->body(), $response->status())->header('Content-Type', 'application/xml');
    }
}
