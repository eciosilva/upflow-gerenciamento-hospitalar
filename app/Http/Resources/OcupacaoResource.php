<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OcupacaoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'paciente' => new PacienteResource($this->paciente),
            'leito' => new LeitoSimpleResource($this->leito),
            'created_at' => $this->created_at,
        ];
    }
}