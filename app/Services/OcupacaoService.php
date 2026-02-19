<?php

namespace App\Services;

use App\Exceptions\OcupacaoException;
use App\Models\Leito;
use App\Models\Ocupacao;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;

class OcupacaoService
{
    /**
     * Interna um paciente em um leito
     */
    public function internar(int $pacienteId, int $leitoId): Ocupacao
    {
        // Verificar se paciente já está internado
        if (Ocupacao::pacienteEstaInternado($pacienteId)) {
            throw new OcupacaoException('Paciente já está internado', 422);
        }

        // Verificar se leito já está ocupado
        if (Ocupacao::leitoEstaOcupado($leitoId)) {
            throw new OcupacaoException('Leito já está ocupado', 422);
        }

        try {
            DB::beginTransaction();

            $ocupacao = Ocupacao::create([
                'paciente_id' => $pacienteId,
                'leito_id' => $leitoId
            ]);

            $ocupacao->load(['paciente', 'leito']);

            DB::commit();

            return $ocupacao;

        } catch (\Exception $e) {
            DB::rollback();
            throw new OcupacaoException('Erro interno do servidor', 500);
        }
    }

    /**
     * Transfere um paciente para outro leito
     */
    public function transferir(int $pacienteId, int $leitoOrigemId, int $leitoDestinoId): Ocupacao
    {
        // Verificar se paciente está internado
        if (!Ocupacao::pacienteEstaInternado($pacienteId)) {
            throw new OcupacaoException('Paciente não está internado', 422);
        }

        // Verificar se associação atual corresponde
        if (!Ocupacao::associacaoExiste($pacienteId, $leitoOrigemId)) {
            throw new OcupacaoException('Paciente não está no leito de origem informado', 422);
        }

        // Verificar se leito de destino está vazio
        if (Ocupacao::leitoEstaOcupado($leitoDestinoId)) {
            throw new OcupacaoException('Leito de destino já está ocupado', 422);
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

            return $novaOcupacao;

        } catch (\Exception $e) {
            DB::rollback();
            throw new OcupacaoException('Erro interno do servidor', 500);
        }
    }

    /**
     * Desocupa um leito (remove associação entre paciente e leito)
     */
    public function desocupar(?int $pacienteId = null, ?int $leitoId = null): array
    {
        // Deve receber pelo menos um parâmetro
        if (!$pacienteId && !$leitoId) {
            throw new OcupacaoException('É obrigatório informar paciente_id ou leito_id', 400);
        }

        // Validar IDs se fornecidos
        if ($pacienteId && !Paciente::find($pacienteId)) {
            throw new OcupacaoException('Paciente não encontrado', 404);
        }

        if ($leitoId && !Leito::find($leitoId)) {
            throw new OcupacaoException('Leito não encontrado', 404);
        }

        try {
            DB::beginTransaction();

            $ocupacao = $this->encontrarOcupacao($pacienteId, $leitoId);

            if (!$ocupacao) {
                throw new OcupacaoException('Ocupação não encontrada', 404);
            }

            $ocupacao->load(['paciente', 'leito']);
            
            $ocupacaoData = [
                'paciente' => $ocupacao->paciente,
                'leito' => $ocupacao->leito,
            ];

            $ocupacao->delete();
            
            DB::commit();

            return $ocupacaoData;

        } catch (OcupacaoException $e) {
            DB::rollback();
            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            throw new OcupacaoException('Erro interno do servidor', 500);
        }
    }

    /**
     * Encontra ocupação baseada nos parâmetros fornecidos
     */
    private function encontrarOcupacao(?int $pacienteId, ?int $leitoId): ?Ocupacao
    {
        if ($pacienteId && $leitoId) {
            // Ambos fornecidos - verificar se associação existe
            if (!Ocupacao::associacaoExiste($pacienteId, $leitoId)) {
                throw new OcupacaoException('Associação não encontrada', 404);
            }

            return Ocupacao::where('paciente_id', $pacienteId)
                          ->where('leito_id', $leitoId)
                          ->whereNull('deleted_at')
                          ->first();

        } elseif ($pacienteId) {
            // Apenas paciente - verificar se está internado
            if (!Ocupacao::pacienteEstaInternado($pacienteId)) {
                throw new OcupacaoException('Paciente não está internado', 422);
            }

            return Ocupacao::where('paciente_id', $pacienteId)
                          ->whereNull('deleted_at')
                          ->first();

        } elseif ($leitoId) {
            // Apenas leito - verificar se está ocupado
            if (!Ocupacao::leitoEstaOcupado($leitoId)) {
                throw new OcupacaoException('Leito não está ocupado', 422);
            }

            return Ocupacao::where('leito_id', $leitoId)
                          ->whereNull('deleted_at')
                          ->first();
        }

        return null;
    }
}