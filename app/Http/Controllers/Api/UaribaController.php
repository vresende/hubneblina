<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UaribaController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obter o XML em qualquer formato aceito
        $xmlContent = $this->extractXmlContent($request);

        if (empty($xmlContent)) {
            return response()->json(['message' => 'Não foi possível extrair o XML da requisição.'], 400);
        }

        // 2. Login programático do usuário
        if (!$this->loginAsUaribaUser()) {
            return response()->json(['message' => 'Usuário uariba@grupomater.com.br não encontrado.'], 403);
        }

        // 3. Enviar o XML para o endpoint externo
        $response = $this->sendToExternalEndpoint($xmlContent);

        // 4. Retornar resposta
        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Extrai o conteúdo XML da requisição (arquivo, base64 ou XML puro).
     */
    private function extractXmlContent(Request $request): ?string
    {
        // Cenário 1: Arquivo (form-data)
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            Log::info('UaribaController: Recebido XML via upload de arquivo.');
            return $content;
        }

        // Cenário 2 e 3: Body (Base64 ou XML puro)
        $body = $request->getContent();

        if (empty($body)) {
            Log::warning('UaribaController: Corpo da requisição vazio.');
            return null;
        }

        // Heurística simples para tentar detectar se é Base64 (não perfeito, mas suficiente para esse caso)
        if ($this->isBase64($body)) {
            $decoded = base64_decode($body, true);
            if ($decoded === false) {
                Log::warning('UaribaController: Base64 inválido.');
                return null;
            }
            Log::info('UaribaController: Recebido XML via body Base64.');
            return $decoded;
        }

        // Se não for base64, assume que é XML puro
        Log::info('UaribaController: Recebido XML como texto puro.');
        return $body;
    }

    /**
     * Faz o login programático como o usuário ARIBA.
     */
    private function loginAsUaribaUser(): bool
    {
        $user = User::where('email', 'uariba@grupomater.com.br')->first();

        if (!$user) {
            Log::error('UaribaController: Usuário uariba@grupomater.com.br não encontrado.');
            return false;
        }

        Auth::login($user);
        Log::info('UaribaController: Usuário autenticado automaticamente.');
        return true;
    }

    /**
     * Envia o XML ao endpoint externo.
     */
    private function sendToExternalEndpoint(string $xmlContent)
    {
        $username = 'ARIBA';
        $password = '123456';

        Log::info('UaribaController: Enviando XML ao endpoint externo.');

        return Http::withoutVerifying()
            ->withBasicAuth($username, $password)
            ->withHeaders([
                'Content-Type' => 'application/xml',
            ])
            ->send('POST', 'http://integracao.grupomater.com.br:883/U_ARIBAADV.APL', [
                'body' => $xmlContent,
            ]);
    }

    /**
     * Verifica se uma string parece ser Base64.
     */
    private function isBase64(string $string): bool
    {
        // Remover quebras de linha comuns em Base64
        $string = str_replace(["\r", "\n"], '', $string);

        // Base64 válido deve ter tamanho múltiplo de 4
        if (strlen($string) % 4 !== 0) {
            return false;
        }

        // Validar com regex
        return preg_match('/^[a-zA-Z0-9\/+\r\n]*={0,2}$/', $string);
    }
}
