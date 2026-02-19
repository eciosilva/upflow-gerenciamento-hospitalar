<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'nome',
        'cpf'
    ];

    /**
     * Relacionamento: Um paciente pode ter uma ocupação ativa
     */
    public function ocupacaoAtiva()
    {
        return $this->hasOne(Ocupacao::class, 'paciente_id');
    }

    /**
     * Relacionamento: Histórico de todas as ocupações do paciente
     */
    public function ocupacoes()
    {
        return $this->hasMany(Ocupacao::class, 'paciente_id');
    }

    /**
     * Escopo: Pacientes internados
     */
    public function scopeInternados($query)
    {
        return $query->whereHas('ocupacaoAtiva');
    }

    /**
     * Escopo: Pacientes não internados
     */
    public function scopeNaoInternados($query)
    {
        return $query->whereDoesntHave('ocupacaoAtiva');
    }

    /**
     * Verifica se o paciente está internado
     */
    public function getEstaInternadoAttribute(): bool
    {
        return $this->ocupacaoAtiva()->exists();
    }

    /**
     * Retorna o leito atual do paciente, se houver
     */
    public function getLeitoAtualAttribute()
    {
        return $this->ocupacaoAtiva?->leito;
    }

    /**
     * Formatar CPF para exibição
     */
    public function getCpfFormatadoAttribute(): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }

    /**
     * Mutator para limpar CPF antes de salvar
     */
    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = preg_replace('/\D/', '', $value);
    }
}