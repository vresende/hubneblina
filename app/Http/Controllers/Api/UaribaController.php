<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UaribaRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UaribaController extends Controller
{
    public function index(UaribaRequest $request): ?JsonResponse
    {
//dd($request);
        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            logger('FALHA AO LOGAR', $request->toArray());
            return response()->json([
                'success' => false,
                'errors' => 'Invalid credentials'
            ], 401);
        }

        $user = auth()->user();

        // Armazena o arquivo temporariamente
        $filePath = $request->file('catalog_file')->store('catalogs');
        $fileFullPath = Storage::path($filePath);

        try {
            // Envia o arquivo para o endpoint externo usando a Facade Http
            $response = Http::attach(
                'catalog_file',
                file_get_contents($fileFullPath),
                $request->file('catalog_file')?->getClientOriginalName()
            )->withOptions([
                'verify' => false, // Ignora a verificação do certificado
                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2, // Força o uso do TLS 1.2
                ],
            ])->post('http://integracao.grupomater.com.br:883/U_ARIBAADV.APL');
            unset($fileFullPath);
            // Verifica o status da resposta
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Arquivo recebido e enviado com sucesso'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'errors' => 'Erro ao enviar o arquivo'
            ], $response->status());
        } catch (Exception $e) {
            unset($fileFullPath);
            logger('FALHA AO ENVIAR PARA A  PJ NEBLINA', ['exception' => $e->getMessage(), $request->toArray()]);
            return response()->json([
                'success' => false,
                'errors' => 'Erro ao enviar o arquivo: ' . $e->getMessage()
            ], 400);
        }
    }

}
