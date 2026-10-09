<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ParticipanteImportValidator
{
    public static function normalize(array $row): array
    {
        foreach (['nome', 'email', 'cpf', 'telefone'] as $field) {
            if (is_scalar($row[$field] ?? null)) {
                $row[$field] = preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', (string) $row[$field]);
            }
        }

        if (is_string($row['email'] ?? null)) {
            $row['email'] = mb_strtolower($row['email']);
        }

        foreach (['cpf', 'telefone'] as $field) {
            if (is_string($row[$field] ?? null)) {
                $digits = preg_replace('/\D+/', '', $row[$field]);
                $row[$field] = $digits === '' ? null : $digits;
            }
        }

        $row['nome'] ??= '';
        $row['email'] ??= '';
        $row['cpf'] ??= null;
        $row['telefone'] ??= null;

        return $row;
    }

    public function validate(array $rows): array
    {
        return $this->validateRows($rows);
    }

    protected function validateRows(array $rows, array $additionalRules = [], array $additionalMessages = []): array
    {
        $errors = [];

        $rules = [
            'nome' => 'required|string|max:255',
            'email' => 'nullable|string|email:rfc|max:255',
            'cpf' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:255',
        ];
        $rules = array_merge($rules, $additionalRules);

        foreach ($rows as $index => &$row) {
            $row = self::normalize($row);
            $validator = Validator::make($row, $rules, array_merge([
                'nome.required' => 'Informe o nome.',
                'nome.string' => 'Informe um nome válido.',
                'nome.max' => 'O nome deve ter no máximo 255 caracteres.',
                'email.email' => 'Informe um e-mail válido.',
                'email.string' => 'Informe um e-mail válido.',
                'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
                'cpf.string' => 'Informe um CPF em formato de texto.',
                'cpf.max' => 'O CPF excede o tamanho permitido.',
                'telefone.string' => 'Informe um telefone em formato de texto.',
                'telefone.max' => 'O telefone excede o tamanho permitido.',
            ], $additionalMessages));

            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors["rows.{$index}.{$field}"] = array_map(
                    fn (string $message) => self::location($row, $index).': '.$message,
                    $messages
                );
            }

            if ($row['email'] === '' && $row['cpf'] === null) {
                $errors["rows.{$index}.identificacao"] = self::location($row, $index)
                    .': informe e-mail ou CPF.';
            }
        }
        unset($row);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $rows;
    }

    public static function location(array $row, int $index = 0): string
    {
        $line = $row['linha_original'] ?? $index + 2;

        return 'Linha '.$line;
    }
}
