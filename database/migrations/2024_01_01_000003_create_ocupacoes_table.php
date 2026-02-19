<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ocupacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('leito_id')->constrained('leitos')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Criar índices únicos condicionais para registros ativos (não soft deleted)
        DB::statement('CREATE UNIQUE INDEX unique_paciente_ativo ON ocupacoes (paciente_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX unique_leito_ocupado ON ocupacoes (leito_id) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover índices condicionais antes de dropar a tabela
        DB::statement('DROP INDEX IF EXISTS unique_paciente_ativo');
        DB::statement('DROP INDEX IF EXISTS unique_leito_ocupado');
        
        Schema::dropIfExists('ocupacoes');
    }
};