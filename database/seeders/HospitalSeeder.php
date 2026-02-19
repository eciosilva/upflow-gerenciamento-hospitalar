<?php

namespace Database\Seeders;

use App\Models\Leito;
use App\Models\Ocupacao;
use App\Models\Paciente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class HospitalSeeder extends Seeder
{
    /**
     * Seed do banco de dados com dados de exemplo
     */
    public function run(): void
    {
        // Criar leitos apenas se a tabela estiver vazia
        if (Leito::count() === 0) {
            Log::info('Criando 10 leitos...');
            for ($i = 1; $i <= 10; $i++) {
                Leito::create([]);
            }
            Log::info('Leitos criados com sucesso!');
        } else {
            Log::info('Leitos já existem na base de dados. Pulando criação...');
        }

        // Criar pacientes usando updateOrCreate (evita duplicação por CPF)
        $pacientes = [
            ['nome' => 'João Silva', 'cpf' => '12345678901'],
            ['nome' => 'Maria Santos', 'cpf' => '98765432109'],
            ['nome' => 'Pedro Oliveira', 'cpf' => '45678912345'],
            ['nome' => 'Ana Costa', 'cpf' => '78912345678'],
            ['nome' => 'José Ferreira', 'cpf' => '32165498701'],
        ];

        Log::info('Criando/atualizando pacientes...');
        $pacientesIds = [];
        foreach ($pacientes as $pacienteData) {
            $paciente = Paciente::updateOrCreate(
                ['cpf' => $pacienteData['cpf']], // Buscar por CPF
                ['nome' => $pacienteData['nome']] // Atualizar nome se necessário
            );
            $pacientesIds[] = $paciente->id;
        }
        Log::info('Pacientes criados/atualizados com sucesso!');

        // Criar ocupações apenas se não existirem (verificar por paciente + leito)
        $ocupacoes = [
            ['paciente_index' => 0, 'leito_id' => 1], // João Silva - Leito 1
            ['paciente_index' => 1, 'leito_id' => 3], // Maria Santos - Leito 3  
            ['paciente_index' => 2, 'leito_id' => 7], // Pedro Oliveira - Leito 7
        ];

        Log::info('Criando ocupações...');
        foreach ($ocupacoes as $ocupacaoData) {
            $pacienteId = $pacientesIds[$ocupacaoData['paciente_index']];
            $leitoId = $ocupacaoData['leito_id'];

            // Verificar se já existe ocupação ativa para esse paciente ou esse leito
            $ocupacaoExiste = Ocupacao::where(function($query) use ($pacienteId, $leitoId) {
                $query->where('paciente_id', $pacienteId)
                      ->orWhere('leito_id', $leitoId);
            })->whereNull('deleted_at')->exists();

            if (!$ocupacaoExiste) {
                Ocupacao::create([
                    'paciente_id' => $pacienteId,
                    'leito_id' => $leitoId,
                ]);
                Log::info("Ocupação criada: Paciente {$pacienteId} -> Leito {$leitoId}");
            } else {
                Log::info("Ocupação já existe para Paciente {$pacienteId} ou Leito {$leitoId}. Pulando...");
            }
        }

        Log::info('Seeder executado com sucesso!');
    }
}