<?php

use App\Http\Controllers\AutenticacaoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auditoria')->prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AutenticacaoController::class, 'registrar'])->middleware('throttle:10,1');
    Route::post('/auth/login', [AutenticacaoController::class, 'entrar'])->middleware('throttle:10,1');
    Route::middleware('jwt')->group(function (): void {
        Route::get('/auth/me', [AutenticacaoController::class, 'usuarioAtual']);
        Route::get('/auth/claims', [AutenticacaoController::class, 'alegacoes']);
        Route::get('/auth/authorities', [AutenticacaoController::class, 'autoridades']);
        Route::get('/user/profile', [AutenticacaoController::class, 'perfil']);
    });
});
