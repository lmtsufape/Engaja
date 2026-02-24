<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissoes = [
            'user.ver','user.criar','user.editar','user.excluir',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
            'evento.ver','evento.criar','evento.editar','evento.excluir',
            'atividade.ver','atividade.criar','atividade.editar','atividade.excluir',
            'inscricao.ver','inscricao.criar','inscricao.editar','inscricao.excluir',
            'presenca.registrar', 'relatorio.ver', 'presenca.import', 'presenca.abrir',
            'regiao.criar','regiao.editar','regiao.excluir',
            'estado.criar','estado.editar','estado.excluir',
            'municipio.criar','municipio.editar','municipio.excluir',
            'dimensao.ver','dimensao.criar','dimensao.editar','dimensao.excluir',
            'indicador.ver','indicador.criar','indicador.editar','indicador.excluir',
            'evidencia.ver','evidencia.criar','evidencia.editar','evidencia.excluir',
            'escala.ver','escala.criar','escala.editar','escala.excluir',
            'templateAvaliacao.ver','templateAvaliacao.criar','templateAvaliacao.editar','templateAvaliacao.excluir',
            'avaliacao.ver','avaliacao.criar','avaliacao.editar','avaliacao.excluir',
            'questao.ver','questao.criar','questao.editar','questao.excluir',
            'certificado.emitir','certificado.editar', 'certificado.baixar',
            'modeloCertificado.criar','modeloCertificado.editar','modeloCertificado.excluir',
        ];

        foreach ($permissoes as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin        = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $gerente       = Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web']);
        $eq_pedagogica     = Role::firstOrCreate(['name' => 'eq_pedagogica', 'guard_name' => 'web']);
        $articulador      = Role::firstOrCreate(['name' => 'articulador', 'guard_name' => 'web']);
        $participante = Role::firstOrCreate(['name' => 'participante', 'guard_name' => 'web']);

        $admin->givePermissionTo([
            'user.ver','user.criar','user.editar','user.excluir',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
            'evento.ver','evento.criar','evento.editar','evento.excluir',
            'atividade.ver','atividade.criar','atividade.editar','atividade.excluir',
            'inscricao.ver','inscricao.criar','inscricao.editar','inscricao.excluir',
            'presenca.registrar', 'relatorio.ver', 'presenca.import', 'presenca.abrir',
            'regiao.criar','regiao.editar','regiao.excluir',
            'estado.criar','estado.editar','estado.excluir',
            'municipio.criar','municipio.editar','municipio.excluir',
            'dimensao.ver','dimensao.criar','dimensao.editar','dimensao.excluir',
            'indicador.ver','indicador.criar','indicador.editar','indicador.excluir',
            'evidencia.ver','evidencia.criar','evidencia.editar','evidencia.excluir',
            'escala.ver','escala.criar','escala.editar','escala.excluir',
            'templateAvaliacao.ver','templateAvaliacao.criar','templateAvaliacao.editar','templateAvaliacao.excluir',
            'avaliacao.ver','avaliacao.criar','avaliacao.editar','avaliacao.excluir',
            'questao.ver','questao.criar','questao.editar','questao.excluir',
            'certificado.emitir','certificado.editar', 'certificado.baixar',
            'modeloCertificado.criar','modeloCertificado.editar','modeloCertificado.excluir',
        ]);

        // GERENTE DE PROJETO
        $gerente->givePermissionTo([
            'user.ver','user.criar','user.editar',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
            'evento.ver','evento.criar','evento.editar',
            'atividade.ver','atividade.criar','atividade.editar','atividade.excluir',
            'inscricao.ver','inscricao.criar','inscricao.editar',
            'presenca.registrar', 'relatorio.ver', 'presenca.import', 'presenca.abrir',
            'dimensao.ver','dimensao.criar','dimensao.editar','dimensao.excluir',
            'indicador.ver','indicador.criar','indicador.editar','indicador.excluir',
            'evidencia.ver','evidencia.criar','evidencia.editar','evidencia.excluir',
            'escala.ver','escala.criar','escala.editar','escala.excluir',
            'templateAvaliacao.ver','templateAvaliacao.criar','templateAvaliacao.editar','templateAvaliacao.excluir',
            'avaliacao.ver','avaliacao.criar','avaliacao.editar','avaliacao.excluir',
            'questao.ver','questao.criar','questao.editar','questao.excluir',
            'certificado.emitir','certificado.editar', 'certificado.baixar',
            'modeloCertificado.criar','modeloCertificado.editar'
        ]);

        // EQUIPE PEDAGOGICA
        $eq_pedagogica->givePermissionTo([
            'user.ver','user.criar','user.editar',
            'participante.ver','participante.criar',
            'evento.ver','evento.criar','evento.editar',
            'atividade.ver','atividade.criar','atividade.editar',
            'inscricao.ver','inscricao.criar','inscricao.editar',
            'presenca.registrar', 'relatorio.ver', 'presenca.import', 'presenca.abrir',
            'dimensao.ver','dimensao.criar','dimensao.editar','dimensao.excluir',
            'indicador.ver','indicador.criar','indicador.editar','indicador.excluir',
            'evidencia.ver','evidencia.criar','evidencia.editar','evidencia.excluir',
            'escala.ver','escala.criar','escala.editar','escala.excluir',
            'templateAvaliacao.ver','templateAvaliacao.criar','templateAvaliacao.editar','templateAvaliacao.excluir',
            'avaliacao.ver','avaliacao.criar','avaliacao.editar','avaliacao.excluir',
            'questao.ver','questao.criar','questao.editar','questao.excluir',
            'certificado.baixar',
        ]);

        // ARTICULADOR
        $articulador->givePermissionTo([
            'user.ver','user.criar','user.editar',
            'participante.ver',
            'evento.ver',
            'atividade.ver',
            'inscricao.ver',
            'presenca.registrar', 'relatorio.ver', 'presenca.import', 'presenca.abrir',
            'dimensao.ver','dimensao.criar','dimensao.editar',
            'indicador.ver','indicador.criar','indicador.editar',
            'evidencia.ver','evidencia.criar','evidencia.editar',
            'escala.ver','escala.criar','escala.editar',
            'templateAvaliacao.ver','templateAvaliacao.criar','templateAvaliacao.editar',
            'avaliacao.ver','avaliacao.criar','avaliacao.editar',
            'questao.ver','questao.criar','questao.editar',
            'certificado.baixar',


        ]);

        // PARTICIPANTE
        $participante->givePermissionTo([
            'inscricao.ver','inscricao.criar',
        ]);
    }
}
