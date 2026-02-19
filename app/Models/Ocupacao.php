<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ocupacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ocupacoes';

    protected $fillable = [
        'paciente_id',
        'leito_id'
    ];

    /**
     * Relacionamento: Ocupação pertence a um paciente
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Relacionamento: Ocupação pertence a um leito
     */
    public function leito()
    {
        return $this->belongsTo(Leito::class, 'leito_id');
    }

    /**
     * Escopo: Apenas ocupações ativas (não soft deleted)
     */
    public function scopeAtivas($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Verifica se um paciente específico está internado
     */
    public static function pacienteEstaInternado(int $pacienteId): bool
    {
        return static::where('paciente_id', $pacienteId)
                     ->whereNull('deleted_at')
                     ->exists();
    }

    /**
     * Verifica se um leito específico está ocupado
     */
    public static function leitoEstaOcupado(int $leitoId): bool
    {
        return static::where('leito_id', $leitoId)
                     ->whereNull('deleted_at')
                     ->exists();
    }

    /**
     * Verifica se uma associação específica existe
     */
    public static function associacaoExiste(int $pacienteId, int $leitoId): bool
    {
        return static::where('paciente_id', $pacienteId)
                     ->where('leito_id', $leitoId)
                     ->whereNull('deleted_at')
                     ->exists();
    }

    /**
     * Retorna o leito atual de um paciente
     */
    public static function getLeitoAtual(int $pacienteId): ?Leito
    {
        $ocupacao = static::where('paciente_id', $pacienteId)
                          ->whereNull('deleted_at')
                          ->first();
        
        return $ocupacao?->leito;
    }

    /**
     * Retorna o paciente atual de um leito
     */
    public static function getPacienteAtual(int $leitoId): ?Paciente
    {
        $ocupacao = static::where('leito_id', $leitoId)
                          ->whereNull('deleted_at')
                          ->first();
        
        return $ocupacao?->paciente;
    }
}