<?php

use App\Http\Controllers\Api\LeitoController;
use App\Http\Controllers\Api\OcupacaoController;
use App\Http\Controllers\Api\PacienteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas da API REST para gestão de ocupação de leitos hospitalares
|
*/

// Rotas de Leitos
Route::get('/leitos', [LeitoController::class, 'index']);
Route::get('/leito/{id}', [LeitoController::class, 'show']);

// Rotas de Pacientes  
Route::get('/pacientes', [PacienteController::class, 'index']);
Route::get('/paciente/{id}', [PacienteController::class, 'show']);

// Busca paciente por CPF (query parameter)
Route::get('/paciente', [PacienteController::class, 'buscarPorCpf']);

// Rotas de Ocupação
Route::post('/ocupacao', [OcupacaoController::class, 'store']);     // Criar ocupação
Route::put('/ocupacao', [OcupacaoController::class, 'update']);     // Transferir paciente
Route::delete('/ocupacao', [OcupacaoController::class, 'destroy']); // Remover ocupação

// Rota de Status da API
Route::get('/status', function () {
    return [
        'api' => 'Hospital Leitos API',
        'version' => '1.0',
        'status' => 'online',
        'timestamp' => now()->toISOString()
    ];
});