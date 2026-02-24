<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    //aqui retorna a colecao de usuarios p fzer a exportação
    public function collection()
    {
        return User::with('participante')
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['administrador', 'gestor']))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'CPF',
            'Telefone',
            'Município',
            'Tipo de organização',
            'Organização',
            'Vínculo',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->participante->cpf ?? null,
            $user->participante->telefone ?? null,
            $user->participante->municipio->nome ?? null,
            $user->participante->tipo_organizacao ?? null,
            $user->participante->escola_unidade ?? null,
            $user->participante->tag ?? null,
        ];
    }
}
