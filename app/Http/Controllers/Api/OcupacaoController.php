<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OcupacaoException;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeitoSimpleResource;
use App\Http\Resources\OcupacaoResource;
use App\Http\Resources\PacienteResource;
use App\Services\OcupacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OcupacaoController extends Controller
{
    public function __construct(
        private OcupacaoService $ocupacaoService
    ) {}
    /**
     * POST /api/ocupacao
     * Associa um paciente a um leito
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'leito_id' => 'required|integer|exists:leitos,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $ocupacao = $this->ocupacaoService->internar(
                $request->paciente_id,
                $request->leito_id
            );

            return response()->json([
                'success' => true,
                'data' => new OcupacaoResource($ocupacao),
                'message' => 'Ocupação criada com sucesso'
            ], 201);

        } catch (OcupacaoException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getHttpCode());
        }
    }

    /**
     * PUT /api/ocupacao
     * Transfere paciente para novo leito (remove associação anterior e cria nova)
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'leito_origem_id' => 'required|integer|exists:leitos,id',
            'leito_destino_id' => 'required|integer|exists:leitos,id|different:leito_origem_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $novaOcupacao = $this->ocupacaoService->transferir(
                $request->paciente_id,
                $request->leito_origem_id,
                $request->leito_destino_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $novaOcupacao->id,
                    'paciente' => new PacienteResource($novaOcupacao->paciente),
                    'leito_anterior' => new LeitoSimpleResource((object) ['id' => $request->leito_origem_id]),
                    'leito_atual' => new LeitoSimpleResource($novaOcupacao->leito),
                    'created_at' => $novaOcupacao->created_at,
                ],
                'message' => 'Transferência realizada com sucesso'
            ]);

        } catch (OcupacaoException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getHttpCode());
        }
    }

    /**
     * DELETE /api/ocupacao
     * Remove associação entre paciente e leito
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $ocupacaoData = $this->ocupacaoService->desocupar(
                $request->input('paciente_id'),
                $request->input('leito_id')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'paciente' => new PacienteResource($ocupacaoData['paciente']),
                    'leito' => new LeitoSimpleResource($ocupacaoData['leito']),
                ],
                'message' => 'Ocupação removida com sucesso'
            ]);

        } catch (OcupacaoException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getHttpCode());
        }
    }
}