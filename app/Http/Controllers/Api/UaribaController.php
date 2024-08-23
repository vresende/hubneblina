<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UaribaRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UaribaController extends Controller
{
    public function index(UaribaRequest $request): ?JsonResponse
    {
        // Verifica as credenciais do usuário
        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            logger('FALHA AO LOGAR', $request->toArray());
            return response()->json([
                'success' => false,
                'errors' => 'Invalid credentials'
            ], 401);
        }

        // Armazena o arquivo temporariamente
        $filePath = $request->file('catalog_file')->store('catalogs');
        $fileFullPath = Storage::path($filePath);

        try {
            // Lê o conteúdo do arquivo XML
            $xmlContent = file_get_contents($fileFullPath);

            // Envia o arquivo como XML no corpo da requisição
            $response = Http::withOptions([
                'verify' => false, // Ignora a verificação do certificado
                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2, // Força o uso do TLS 1.2
                ],
            ])->withHeaders([
                'Content-Type' => 'application/xml', // Define o tipo de conteúdo como XML
            ])->post('http://integracao.grupomater.com.br:883/U_ARIBAADV.APL', $xmlContent);

            unset($fileFullPath);

            // Verifica o status da resposta
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Arquivo recebido e enviado com sucesso'
                ], $response->status());
            }

        } catch (Exception $e) {
            unset($fileFullPath);
            logger('FALHA AO ENVIAR PARA A PJ NEBLINA', ['exception' => $e->getMessage(), $request->toArray()]);
            return response()->json([
                'success' => false,
                'errors' => 'Erro ao enviar o arquivo: ' . $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => false,
            'errors' => 'Erro ao enviar o arquivo: ' . $e->getMessage()
        ], 400);
    }
}
