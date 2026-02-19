<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leito extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leitos';

    protected $fillable = [];

    /**
     * Relacionamento: Um leito pode ter uma ocupação ativa
     */
    public function ocupacaoAtiva()
    {
        return $this->hasOne(Ocupacao::class, 'leito_id');
    }

    /**
     * Relacionamento: Histórico de todas as ocupações do leito
     */
    public function ocupacoes()
    {
        return $this->hasMany(Ocupacao::class, 'leito_id');
    }

    /**
     * Escopo: Leitos disponíveis (não ocupados)
     */
    public function scopeDisponiveis($query)
    {
        return $query->whereDoesntHave('ocupacaoAtiva');
    }

    /**
     * Escopo: Leitos ocupados
     */
    public function scopeOcupados($query)
    {
        return $query->whereHas('ocupacaoAtiva');
    }

    /**
     * Verifica se o leito está ocupado
     */
    public function getEstaOcupadoAttribute(): bool
    {
        return $this->ocupacaoAtiva()->exists();
    }

    /**
     * Retorna o paciente que ocupa o leito, se houver
     */
    public function getPacienteAtualAttribute()
    {
        return $this->ocupacaoAtiva?->paciente;
    }
}