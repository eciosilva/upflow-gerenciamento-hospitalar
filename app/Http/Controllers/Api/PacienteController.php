<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarPacientePorCpfRequest;
use App\Http\Resources\PacienteCompletoResource;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    /**
     * GET /api/pacientes
     * Lista todos os pacientes com status de internação
     */
    public function index(): JsonResponse
    {
        $pacientes = Paciente::with(['ocupacaoAtiva.leito'])->get();

        return response()->json([
            'success' => true,
            'data' => PacienteCompletoResource::collection($pacientes),
            'message' => 'Pacientes listados com sucesso'
        ]);
    }

    /**
     * GET /api/paciente/{id}
     * Retorna um paciente específico com status de internação
     */
    public function show(int $id): JsonResponse
    {
        $paciente = Paciente::with(['ocupacaoAtiva.leito'])->find($id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PacienteCompletoResource($paciente),
            'message' => 'Paciente recuperado com sucesso'
        ]);
    }

    /**
     * GET /api/paciente?cpf={cpf}
     * Busca paciente por CPF
     */
    public function buscarPorCpf(BuscarPacientePorCpfRequest $request): JsonResponse
    {
        // CPF já vem validado e limpo pelo FormRequest
        $cpfLimpo = $request->validated('cpf');

        $paciente = Paciente::with(['ocupacaoAtiva.leito'])
                            ->where('cpf', $cpfLimpo)
                            ->first();

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PacienteCompletoResource($paciente),
            'message' => 'Paciente encontrado com sucesso'
        ]);
    }
}