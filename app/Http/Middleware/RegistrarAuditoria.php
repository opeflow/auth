<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RegistrarAuditoria
{
    public function handle(Request $request, Closure $proximo)
    {
        $inicio = microtime(true);
        $correlacao = Str::limit($request->header('X-Correlation-Id', (string) Str::uuid()), 64, '');
        $request->attributes->set('correlation_id', $correlacao);

        try {
            $resposta = $proximo($request);
        } catch (Throwable $erro) {
            $status = $erro instanceof ValidationException ? 422 : ($erro instanceof HttpExceptionInterface ? $erro->getStatusCode() : 500);
            $this->registrar($request, $correlacao, $inicio, $status);
            throw $erro;
        }

        $resposta->headers->set('X-Correlation-Id', $correlacao);
        $this->registrar($request, $correlacao, $inicio, $resposta->getStatusCode());

        return $resposta;
    }

    private function registrar(Request $request, string $correlacao, float $inicio, int $status): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $jwt = $request->attributes->get('jwt');
        $sistema = $request->attributes->get('sistema');
        $falha = in_array($status, [401, 403, 429], true);

        try {
            DB::table('log_event')->insert([
                'created_at' => now(),
                'correlation_id' => $correlacao,
                'event_type' => $falha ? 'security_auth_failure' : 'http_request',
                'level' => $status >= 500 ? 'ERROR' : ($falha ? 'WARN' : 'INFO'),
                'subject' => $jwt->sub ?? null,
                'system_id' => $jwt->system_id ?? $sistema->id ?? null,
                'client_ip' => $request->ip(),
                'http_method' => $request->method(),
                'http_path' => '/'.$request->path(),
                'http_status' => $status,
                'latency_ms' => (int) ((microtime(true) - $inicio) * 1000),
                'details_json' => json_encode([], JSON_THROW_ON_ERROR),
            ]);
        } catch (Throwable $erro) {
            Log::warning('Não foi possível persistir evento de auditoria.', ['erro' => $erro->getMessage()]);
        }
    }
}
