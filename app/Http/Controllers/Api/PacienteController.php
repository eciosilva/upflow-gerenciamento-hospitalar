<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarPacientePorCpfRequest;
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
        $pacientes = Paciente::with(['ocupacaoAtiva.leito'])
                             ->get()
                             ->map(function ($paciente) {
                                 return [
                                     'id' => $paciente->id,
                                     'nome' => $paciente->nome,
                                     'cpf' => $paciente->cpf_formatado,
                                     'esta_internado' => $paciente->esta_internado,
                                     'leito' => $paciente->leito_atual ? [
                                         'id' => $paciente->leito_atual->id,
                                     ] : null,
                                     'created_at' => $paciente->created_at,
                                     'updated_at' => $paciente->updated_at,
                                 ];
                             });

        return response()->json([
            'success' => true,
            'data' => $pacientes,
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

        $data = [
            'id' => $paciente->id,
            'nome' => $paciente->nome,
            'cpf' => $paciente->cpf_formatado,
            'esta_internado' => $paciente->esta_internado,
            'leito' => $paciente->leito_atual ? [
                'id' => $paciente->leito_atual->id,
            ] : null,
            'created_at' => $paciente->created_at,
            'updated_at' => $paciente->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
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

        $data = [
            'id' => $paciente->id,
            'nome' => $paciente->nome,
            'cpf' => $paciente->cpf_formatado,
            'esta_internado' => $paciente->esta_internado,
            'leito' => $paciente->leito_atual ? [
                'id' => $paciente->leito_atual->id,
            ] : null,
            'created_at' => $paciente->created_at,
            'updated_at' => $paciente->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Paciente encontrado com sucesso'
        ]);
    }
}