<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leito;
use Illuminate\Http\JsonResponse;

class LeitoController extends Controller
{
    /**
     * GET /api/leitos
     * Lista todos os leitos com status de ocupação
     */
    public function index(): JsonResponse
    {
        $leitos = Leito::with(['ocupacaoAtiva.paciente'])
                       ->get()
                       ->map(function ($leito) {
                           return [
                               'id' => $leito->id,
                               'esta_ocupado' => $leito->esta_ocupado,
                               'paciente' => $leito->paciente_atual ? [
                                   'id' => $leito->paciente_atual->id,
                                   'nome' => $leito->paciente_atual->nome,
                                   'cpf' => $leito->paciente_atual->cpf_formatado,
                               ] : null,
                               'created_at' => $leito->created_at,
                               'updated_at' => $leito->updated_at,
                           ];
                       });

        return response()->json([
            'success' => true,
            'data' => $leitos,
            'message' => 'Leitos listados com sucesso'
        ]);
    }

    /**
     * GET /api/leito/{id}
     * Retorna um leito específico com status de ocupação
     */
    public function show(int $id): JsonResponse
    {
        $leito = Leito::with(['ocupacaoAtiva.paciente'])->find($id);

        if (!$leito) {
            return response()->json([
                'success' => false,
                'message' => 'Leito não encontrado'
            ], 404);
        }

        $data = [
            'id' => $leito->id,
            'esta_ocupado' => $leito->esta_ocupado,
            'paciente' => $leito->paciente_atual ? [
                'id' => $leito->paciente_atual->id,
                'nome' => $leito->paciente_atual->nome,
                'cpf' => $leito->paciente_atual->cpf_formatado,
            ] : null,
            'created_at' => $leito->created_at,
            'updated_at' => $leito->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Leito recuperado com sucesso'
        ]);
    }
}