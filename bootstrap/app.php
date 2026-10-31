<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => App\Http\Middleware\VerificarTokenKeycloak::class,
            'auditoria' => App\Http\Middleware\RegistrarAuditoria::class,
            'admin.auth' => App\Http\Middleware\AutenticarAdministrador::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*'));
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }
            return response()->json(['status'=>'error','message'=>'Erro de validação','data'=>$e->errors()], 422)
                ->header('X-Correlation-Id', (string) $request->attributes->get('correlation_id', ''));
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $status === 404 ? 'Recurso não encontrado' : ($status >= 500 ? 'Erro interno no servidor' : $e->getMessage());
            return response()->json(['status'=>'error','message'=>$message,'data'=>null], $status)
                ->header('X-Correlation-Id', (string) $request->attributes->get('correlation_id', ''));
        });
    })->create();
