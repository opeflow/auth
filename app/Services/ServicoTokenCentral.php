<?php

namespace App\Services;

use Firebase\JWT\JWT;
use RuntimeException;

class ServicoTokenCentral
{
    public function emitir(object $sistema, object $usuario): array
    {
        $agora = time();
        $duracao = (int) config('services.tokens.duracao_segundos', 3600);
        $claims = [
            'iss' => rtrim(config('app.url'), '/'),
            'aud' => $sistema->client_id,
            'sub' => (string) $usuario->id,
            'iat' => $agora,
            'exp' => $agora + $duracao,
            'system_id' => (int) $sistema->id,
            'system' => $sistema->slug,
            'credential_version' => (int) $sistema->versao_credencial,
            'preferred_username' => $usuario->username,
            'email' => $usuario->email,
            'realm_access' => ['roles' => []],
        ];

        $chave = ChaveJwtCentral::obter();
        if ($chave === null) {
            throw new RuntimeException('A chave da aplicação não está configurada com segurança.');
        }

        return [
            'access_token' => JWT::encode($claims, $chave, 'HS256'),
            'token_type' => 'Bearer',
            'expires_in' => $duracao,
            'user' => ['id' => $usuario->id, 'username' => $usuario->username, 'email' => $usuario->email],
            'system' => ['id' => $sistema->id, 'slug' => $sistema->slug, 'name' => $sistema->nome],
        ];
    }
}
