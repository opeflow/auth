<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\ChaveJwtCentral;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class VerificarTokenKeycloak
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token, 401, 'Token ausente');
        $claimsCentrais = null;
        $chaveCentral = ChaveJwtCentral::obter();
        if ($chaveCentral !== null) {
            try {
                $claimsCentrais = (array) JWT::decode($token, new Key($chaveCentral, 'HS256'));
            } catch (Throwable) {
                // Se não for um JWT da central, tenta validar pelas chaves do Keycloak.
            }
        }
        if (($claimsCentrais['iss'] ?? null) === rtrim(config('app.url'), '/') && isset($claimsCentrais['system_id'])) {
            $sistema = DB::table('sistemas')->find($claimsCentrais['system_id']);
            $audiencias = (array) ($claimsCentrais['aud'] ?? []);
            abort_unless($sistema && $sistema->habilitado, 401, 'Sistema desativado.');
            abort_unless(in_array($sistema->client_id, $audiencias, true), 401, 'Token não pertence ao sistema.');
            abort_unless((int) $sistema->versao_credencial === (int) ($claimsCentrais['credential_version'] ?? 0), 401, 'Token revogado.');
            $request->attributes->set('jwt', (object) $claimsCentrais);

            return $next($request);
        }

        try {
            $issuer = rtrim(config('services.keycloak.issuer'), '/');
            $keys = Cache::remember('keycloak.jwks', 300, function () use ($issuer) {
                $urlInterna = rtrim(config('services.keycloak.base_url'), '/');
                $urlDescoberta = $urlInterna.'/realms/'.config('services.keycloak.realm').'/.well-known/openid-configuration';
                $response = Http::timeout(5)->get($urlDescoberta);
                $response->throw();
                $jwksUri = $response->json('jwks_uri');
                abort_unless($jwksUri, 503, 'JWKS indisponível');
                $caminhoJwks = parse_url($jwksUri, PHP_URL_PATH);
                abort_unless($caminhoJwks, 503, 'Endereço JWKS inválido');
                $jwks = Http::timeout(5)->get($urlInterna.$caminhoJwks);
                $jwks->throw();
                return JWK::parseKeySet($jwks->json());
            });
            $claims = (array) JWT::decode($token, $keys);
            abort_unless(($claims['iss'] ?? null) === $issuer, 401, 'Issuer inválido');
            $destinatarios = array_map('strval', (array) ($claims['aud'] ?? []));
            $clienteAutorizado = config('services.keycloak.clientes_permitidos', []);
            $clienteAutorizado = array_map('strval', $clienteAutorizado);
            $clienteDoToken = (string) ($claims['azp'] ?? '');
            abort_unless(array_intersect($clienteAutorizado, $destinatarios) || in_array($clienteDoToken, $clienteAutorizado, true), 401, 'Audiência do token inválida.');
            $request->attributes->set('jwt', (object) $claims);
        } catch (Throwable $e) {
            abort(401, 'Token inválido');
        }
        return $next($request);
    }
}
