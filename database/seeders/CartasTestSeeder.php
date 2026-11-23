<?php

namespace Database\Seeders;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CartasTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buscar admin Cartas criado por CartasAdminSeeder (com fallback seguro)
        $adminCartas = User::firstOrCreate(
            [
                'email' => 'admin.cartas@example.com',
                'sistema_origem' => User::SISTEMA_CARTAS,
            ],
            [
                'name' => 'Admin Cartas (Fallback)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Criar evento Cartas com is_cartas=true
        $eventCartas = Evento::firstOrCreate(
            ['nome' => 'Cartas para Esperançar - 2025'],
            [
                'user_id' => $adminCartas->id,
                'tipo' => 'Cartas para Esperançar',
                'is_cartas' => true,
                'data_inicio' => now()->startOfYear(),
                'data_fim' => now()->endOfYear(),
                'acao_geral' => '1',
                'subacao' => '1.1 - Mapeamento inicial - Leitura do Mundo',
            ]
        );

        // 3. Obter roles criadas por CartasAdminSeeder
        $adminRole = Role::where('name', 'cartas_admin')->firstOrFail();
        $gestaoRole = Role::where('name', 'cartas_gestao')->firstOrFail();
        $voluntarioRole = Role::where('name', 'cartas_voluntario')->firstOrFail();

        // 4. Criar usuários Cartas

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin.cartas.test@example.com'],
            [
                'name' => 'Admin Cartas Teste',
                'sistema_origem' => User::SISTEMA_CARTAS,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Gestores
        $gestorNames = ['Jacira Gestora Teste', 'Giovana Gestora Teste'];
        $gestores = [];
        foreach ($gestorNames as $index => $name) {
            $gestor = User::firstOrCreate(
                ['email' => "gestor{$index}.cartas.test@example.com"],
                [
                    'name' => $name,
                    'sistema_origem' => User::SISTEMA_CARTAS,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
            $gestor->syncRoles([$gestaoRole]);
            $gestores[] = $gestor;
        }

        // Voluntários
        $voluntarioNames = [
            'Ana Petrobras Teste',
            'Bruno Petrobras Teste',
            'Carla Petrobras Teste',
            'Daniel Petrobras Teste',
            'Eva Petrobras Teste',
            'Fabio Petrobras Teste', // sem cartas, para testar o estado vazio da tela do voluntário
        ];
        $voluntarios = [];
        foreach ($voluntarioNames as $index => $name) {
            $vol = User::firstOrCreate(
                ['email' => "voluntario{$index}.cartas.test@example.com"],
                [
                    'name' => $name,
                    'sistema_origem' => User::SISTEMA_CARTAS,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
            $vol->syncRoles([$voluntarioRole]);
            $voluntarios[] = $vol;
        }

        // 5. Criar educandos (usuários Engaja + participantes) e inscrever no evento Cartas
        $educandoNames = [
            'João Silva Teste',
            'Maria Santos Teste',
            'Pedro Costa Teste',
            'Ana Oliveira Teste',
            'Carlos Ferreira Teste',
            'Beatriz Martins Teste',
            'Fernando Souza Teste',
            'Gabriela Alves Teste',
            'Henrique Gomes Teste',
            'Iris Rocha Teste',
        ];

        $educandos = [];
        foreach ($educandoNames as $index => $name) {
            $userEngaja = User::firstOrCreate(
                ['email' => "educando{$index}.test@example.com"],
                [
                    'name' => $name,
                    'sistema_origem' => User::SISTEMA_ENGAJA,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $participante = Participante::firstOrCreate(
                ['user_id' => $userEngaja->id],
                [
                    'municipio_id' => null,
                    'telefone' => '11999999999',
                ]
            );

            Inscricao::firstOrCreate(
                ['evento_id' => $eventCartas->id, 'participante_id' => $participante->id],
                []
            );

            $educandos[] = $participante;
        }

        // 6. Criar cartas em vários estados usando factories

        // 3 cartas em rascunho (sem mensagens)
        foreach (range(1, 3) as $i) {
            Carta::factory()->create([
                'educando_participante_id' => $educandos[$i - 1]->id,
                'voluntario_user_id' => null,
                'evento_id' => $eventCartas->id,
                'status' => Carta::STATUS_RASCUNHO,
            ]);
        }

        // 2 cartas em aguardando_voluntario (msg 1 do educando, aprovada)
        foreach (range(1, 2) as $i) {
            $carta = Carta::factory()->create([
                'educando_participante_id' => $educandos[3 + $i - 1]->id,
                'voluntario_user_id' => $voluntarios[$i - 1]->id,
                'evento_id' => $eventCartas->id,
                'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
                'distribuida_em' => now(),
            ]);

            CartaMensagem::factory()->create([
                'carta_id' => $carta->id,
                'rodada' => 1,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
                'remetente_participante_id' => $carta->educando_participante_id,
                'destinatario_user_id' => $carta->voluntario_user_id,
                'status' => CartaMensagem::STATUS_APROVADA,
                'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
            ]);
        }

        // 2 cartas respondidas (msg 1 + 2 ambas aprovadas)
        foreach (range(1, 2) as $i) {
            $carta = Carta::factory()->create([
                'educando_participante_id' => $educandos[5 + $i - 1]->id,
                'voluntario_user_id' => $voluntarios[2 + $i - 1]->id,
                'evento_id' => $eventCartas->id,
                'status' => Carta::STATUS_RESPONDIDA,
                'distribuida_em' => now(),
            ]);

            CartaMensagem::factory()->create([
                'carta_id' => $carta->id,
                'rodada' => 1,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
                'remetente_participante_id' => $carta->educando_participante_id,
                'destinatario_user_id' => $carta->voluntario_user_id,
                'status' => CartaMensagem::STATUS_APROVADA,
                'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
            ]);

            CartaMensagem::factory()->create([
                'carta_id' => $carta->id,
                'rodada' => 2,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
                'remetente_user_id' => $carta->voluntario_user_id,
                'destinatario_participante_id' => $carta->educando_participante_id,
                'status' => CartaMensagem::STATUS_APROVADA,
                'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
                'texto' => 'Olá educando! Fico feliz em corresponder com você. Um abraço!',
            ]);
        }

        // 1 carta em aguardando_verificacao (resposta pendente de verificação)
        $cartaPendente = Carta::factory()->create([
            'educando_participante_id' => $educandos[7]->id,
            'voluntario_user_id' => $voluntarios[4]->id,
            'evento_id' => $eventCartas->id,
            'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
            'distribuida_em' => now(),
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $cartaPendente->id,
            'rodada' => 1,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'remetente_participante_id' => $cartaPendente->educando_participante_id,
            'destinatario_user_id' => $cartaPendente->voluntario_user_id,
            'status' => CartaMensagem::STATUS_APROVADA,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $cartaPendente->id,
            'rodada' => 2,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'remetente_user_id' => $cartaPendente->voluntario_user_id,
            'destinatario_participante_id' => $cartaPendente->educando_participante_id,
            'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'texto' => 'Sua resposta aguarda verificação pela gestão.',
        ]);

        // 1 carta em aguardando_ajuste (resposta rejeitada, ajuste solicitado)
        $cartaAjuste = Carta::factory()->create([
            'educando_participante_id' => $educandos[8]->id,
            'voluntario_user_id' => $voluntarios[0]->id,
            'evento_id' => $eventCartas->id,
            'status' => Carta::STATUS_AGUARDANDO_AJUSTE,
            'distribuida_em' => now(),
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $cartaAjuste->id,
            'rodada' => 1,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'remetente_participante_id' => $cartaAjuste->educando_participante_id,
            'destinatario_user_id' => $cartaAjuste->voluntario_user_id,
            'status' => CartaMensagem::STATUS_APROVADA,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $cartaAjuste->id,
            'rodada' => 2,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'remetente_user_id' => $cartaAjuste->voluntario_user_id,
            'destinatario_participante_id' => $cartaAjuste->educando_participante_id,
            'status' => CartaMensagem::STATUS_AJUSTE_SOLICITADO,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'texto' => 'Resposta que será rejeitada para ajuste.',
            'parecer_verificacao' => 'Por favor, revise o tom e reenvie com mais detalhes sobre suas experiências.',
        ]);

        $this->command->info('✓ CartasTestSeeder: Evento, usuários, participantes, cartas e mensagens criados com sucesso!');
    }
}
