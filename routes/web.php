<?php

use App\Http\Controllers\PainelAdministradorController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [PainelAdministradorController::class, 'login'])->name('admin.login');
Route::post('/admin/login', [PainelAdministradorController::class, 'autenticar'])->middleware('throttle:5,1')->name('admin.autenticar');
Route::middleware('admin.auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [PainelAdministradorController::class, 'inicio'])->name('inicio');
    Route::get('/guia-keycloak', [PainelAdministradorController::class, 'guiaKeycloak'])->name('guia-keycloak');
    Route::post('/sistemas', [PainelAdministradorController::class, 'cadastrarSistema'])->name('sistemas.criar');
    Route::patch('/sistemas/{id}/status', [PainelAdministradorController::class, 'alternarSistema'])->name('sistemas.status');
    Route::patch('/sistemas/{id}/cadastro', [PainelAdministradorController::class, 'alternarCadastro'])->name('sistemas.cadastro');
    Route::post('/sistemas/{id}/rotacionar-segredo', [PainelAdministradorController::class, 'rotacionarSegredo'])->name('sistemas.rotacionar-segredo');
    Route::post('/logout', [PainelAdministradorController::class, 'sair'])->name('sair');
});
