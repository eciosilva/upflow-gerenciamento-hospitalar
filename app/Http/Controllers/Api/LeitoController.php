<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeitoResource;
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
        $leitos = Leito::with(['ocupacaoAtiva.paciente'])->get();

        return response()->json([
            'success' => true,
            'data' => LeitoResource::collection($leitos),
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

        return response()->json([
            'success' => true,
            'data' => new LeitoResource($leito),
            'message' => 'Leito recuperado com sucesso'
        ]);
    }
}