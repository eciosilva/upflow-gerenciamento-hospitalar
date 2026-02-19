<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeitoSimpleResource;
use App\Http\Resources\OcupacaoResource;
use App\Http\Resources\PacienteResource;
use App\Models\Leito;
use App\Models\Ocupacao;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OcupacaoController extends Controller
{
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

        $pacienteId = $request->paciente_id;
        $leitoId = $request->leito_id;

        // Verificar se paciente já está internado
        if (Ocupacao::pacienteEstaInternado($pacienteId)) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente já está internado'
            ], 422);
        }

        // Verificar se leito já está ocupado
        if (Ocupacao::leitoEstaOcupado($leitoId)) {
            return response()->json([
                'success' => false,
                'message' => 'Leito já está ocupado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ocupacao = Ocupacao::create([
                'paciente_id' => $pacienteId,
                'leito_id' => $leitoId
            ]);

            $ocupacao->load(['paciente', 'leito']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new OcupacaoResource($ocupacao),
                'message' => 'Ocupação criada com sucesso'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
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

        $pacienteId = $request->paciente_id;
        $leitoOrigemId = $request->leito_origem_id;
        $leitoDestinoId = $request->leito_destino_id;

        // Verificar se paciente está internado
        if (!Ocupacao::pacienteEstaInternado($pacienteId)) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não está internado'
            ], 422);
        }

        // Verificar se associação atual corresponde
        if (!Ocupacao::associacaoExiste($pacienteId, $leitoOrigemId)) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não está no leito de origem informado'
            ], 422);
        }

        // Verificar se leito de destino está vazio
        if (Ocupacao::leitoEstaOcupado($leitoDestinoId)) {
            return response()->json([
                'success' => false,
                'message' => 'Leito de destino já está ocupado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Remover ocupação atual (soft delete)
            $ocupacaoAtual = Ocupacao::where('paciente_id', $pacienteId)
                                    ->where('leito_id', $leitoOrigemId)
                                    ->whereNull('deleted_at')
                                    ->first();
            
            $ocupacaoAtual->delete();

            // Criar nova ocupação
            $novaOcupacao = Ocupacao::create([
                'paciente_id' => $pacienteId,
                'leito_id' => $leitoDestinoId
            ]);

            $novaOcupacao->load(['paciente', 'leito']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $novaOcupacao->id,
                    'paciente' => new PacienteResource($novaOcupacao->paciente),
                    'leito_anterior' => new LeitoSimpleResource((object) ['id' => $leitoOrigemId]),
                    'leito_atual' => new LeitoSimpleResource($novaOcupacao->leito),
                    'created_at' => $novaOcupacao->created_at,
                ],
                'message' => 'Transferência realizada com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * DELETE /api/ocupacao
     * Remove associação entre paciente e leito
     */
    public function destroy(Request $request): JsonResponse
    {
        $pacienteId = $request->input('paciente_id');
        $leitoId = $request->input('leito_id');

        // Deve receber pelo menos um parâmetro
        if (!$pacienteId && !$leitoId) {
            return response()->json([
                'success' => false,
                'message' => 'É obrigatório informar paciente_id ou leito_id'
            ], 400);
        }

        // Validar IDs se fornecidos
        if ($pacienteId && !Paciente::find($pacienteId)) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado'
            ], 404);
        }

        if ($leitoId && !Leito::find($leitoId)) {
            return response()->json([
                'success' => false,
                'message' => 'Leito não encontrado'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $ocupacao = null;

            if ($pacienteId && $leitoId) {
                // Ambos fornecidos - verificar se associação existe
                if (!Ocupacao::associacaoExiste($pacienteId, $leitoId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Associação não encontrada'
                    ], 404);
                }

                $ocupacao = Ocupacao::where('paciente_id', $pacienteId)
                                   ->where('leito_id', $leitoId)
                                   ->whereNull('deleted_at')
                                   ->first();

            } elseif ($pacienteId) {
                // Apenas paciente - verificar se está internado
                if (!Ocupacao::pacienteEstaInternado($pacienteId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Paciente não está internado'
                    ], 422);
                }

                $ocupacao = Ocupacao::where('paciente_id', $pacienteId)
                                   ->whereNull('deleted_at')
                                   ->first();

            } elseif ($leitoId) {
                // Apenas leito - verificar se está ocupado
                if (!Ocupacao::leitoEstaOcupado($leitoId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Leito não está ocupado'
                    ], 422);
                }

                $ocupacao = Ocupacao::where('leito_id', $leitoId)
                                   ->whereNull('deleted_at')
                                   ->first();
            }

            if ($ocupacao) {
                $ocupacao->load(['paciente', 'leito']);
                
                $ocupacaoData = [
                    'paciente' => new PacienteResource($ocupacao->paciente),
                    'leito' => new LeitoSimpleResource($ocupacao->leito),
                ];

                $ocupacao->delete();
                
                DB::commit();

                return response()->json([
                    'success' => true,
                    'data' => $ocupacaoData,
                    'message' => 'Ocupação removida com sucesso'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ocupação não encontrada'
            ], 404);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}