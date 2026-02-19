<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class BuscarPacientePorCpfRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cpf' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Remove apenas os caracteres de máscara do CPF
                    $cpfLimpo = $this->limparCpf($value);
                    
                    // Verifica se tem exatamente 11 dígitos
                    if (strlen($cpfLimpo) !== 11) {
                        $fail('CPF deve ter 11 dígitos');
                    }
                }
            ]
        ];
    }

    /**
     * Get data to be validated from the request.
     * Busca os dados na query string em vez do body
     */
    public function validationData()
    {
        return $this->query();
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cpf.required' => 'CPF é obrigatório',
            'cpf.string' => 'CPF deve ser uma string'
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Retorna 404 em vez de 422 para CPF inválido
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $message = $errors->first(); // Pega a primeira mensagem de erro
        
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $message
            ], 404)
        );
    }

    /**
     * Get the validated input data for the request.
     * Retorna CPF limpo (sem máscaras)
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Se é um array completo, limpa o CPF
        if (is_array($validated) && isset($validated['cpf'])) {
            $validated['cpf'] = $this->limparCpf($validated['cpf']);
        }
        
        // Se está buscando especificamente o CPF, limpa e retorna
        if ($key === 'cpf' && $validated) {
            return $this->limparCpf($validated);
        }
        
        return $validated;
    }

    /**
     * Remove apenas os caracteres de máscara do CPF (pontos e hífens)
     */
    private function limparCpf(string $cpf): string
    {
        return preg_replace('/[\.\-]/', '', $cpf);
    }
}