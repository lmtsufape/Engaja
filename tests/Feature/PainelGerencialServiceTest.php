<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\User;
use App\Services\PainelGerencialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PainelGerencialServiceTest extends TestCase
{
    use RefreshDatabase;

    private PainelGerencialService $service;

    private int $municipioId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PainelGerencialService::class);
        $this->seedCenario();
    }

    /**
     * Cenário controlado:
     * - 1 região / estado / município
     * - 1 evento Presencial com 2 atividades em 2026-S1 (carga 120min cada = 2h cada)
     * - p1: presente, avaliou, certificado, documentação completa
     * - p2: presente, sem CPF (pendência), tag "Rede de Ensino"
     * - p3: inscrito nas 2 atividades, ausente em ambas (recorrência = 2)
     */
    private function seedCenario(): void
    {
        $regiaoId = DB::table('regiaos')->insertGetId(['nome' => 'Nordeste', 'created_at' => now(), 'updated_at' => now()]);
        $estadoId = DB::table('estados')->insertGetId(['regiao_id' => $regiaoId, 'nome' => 'Pernambuco', 'sigla' => 'PE', 'created_at' => now(), 'updated_at' => now()]);
        $this->municipioId = DB::table('municipios')->insertGetId(['estado_id' => $estadoId, 'nome' => 'Recife', 'created_at' => now(), 'updated_at' => now()]);

        $evento = Evento::factory()->create(['modalidade' => 'Presencial', 'nome' => 'Formação Inicial']);

        $a1 = Atividade::factory()->create([
            'evento_id' => $evento->id, 'municipio_id' => $this->municipioId,
            'dia' => '2026-02-10', 'hora_inicio' => '09:00:00', 'hora_fim' => '11:00:00',
            'publico_esperado' => 10, 'carga_horaria' => 120,
        ]);
        $a2 = Atividade::factory()->create([
            'evento_id' => $evento->id, 'municipio_id' => $this->municipioId,
            'dia' => '2026-03-10', 'hora_inicio' => '09:00:00', 'hora_fim' => '11:00:00',
            'publico_esperado' => 10, 'carga_horaria' => 120,
        ]);

        $p1 = $this->participante(['cpf' => '11111111111', 'telefone' => '81999990000', 'tag' => null]);
        $p2 = $this->participante(['cpf' => null, 'telefone' => '81999991111', 'tag' => 'Rede de Ensino']);
        $p3 = $this->participante(['cpf' => '33333333333', 'telefone' => '81999992222', 'tag' => 'Movimento Social']);

        // Presentes em a1
        $this->presenca($evento->id, $a1->id, $p1, 'presente', avaliou: true, certificado: true);
        $this->presenca($evento->id, $a1->id, $p2, 'presente');

        // p3 inscrito nas 2 atividades, sem presença (ausente) -> 2 ausências
        $this->inscricao($evento->id, $a1->id, $p3);
        $this->inscricao($evento->id, $a2->id, $p3);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function participante(array $attrs): int
    {
        $userId = User::factory()->create()->id;

        return DB::table('participantes')->insertGetId(array_merge([
            'user_id' => $userId,
            'municipio_id' => $this->municipioId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    private function inscricao(int $eventoId, int $atividadeId, int $participanteId): int
    {
        return DB::table('inscricaos')->insertGetId([
            'evento_id' => $eventoId,
            'atividade_id' => $atividadeId,
            'participante_id' => $participanteId,
            'ouvinte' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function presenca(int $eventoId, int $atividadeId, int $participanteId, string $status, bool $avaliou = false, bool $certificado = false): void
    {
        $inscricaoId = $this->inscricao($eventoId, $atividadeId, $participanteId);

        DB::table('presencas')->insert([
            'inscricao_id' => $inscricaoId,
            'atividade_id' => $atividadeId,
            'status' => $status,
            'avaliacao_respondida' => $avaliou,
            'certificado_emitido' => $certificado,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $query = []): array
    {
        return $this->service->buildPayload(new Request($query));
    }

    public function test_kpis_refletem_o_cenario(): void
    {
        $kpis = $this->payload()['kpis'];

        $this->assertSame(1, $kpis['municipios_ativos']);
        $this->assertSame(1, $kpis['eventos_realizados']);
        $this->assertSame(2, $kpis['participantes_totais']);
        $this->assertSame(2, $kpis['participantes_unicos']);
        $this->assertSame(4.0, $kpis['horas_presenciais']); // 2 atividades x 2h
        $this->assertSame(0.0, $kpis['horas_ead']);
        $this->assertSame(1, $kpis['certificados_emitidos']);
        $this->assertSame(1, $kpis['avaliacoes_respondidas']);
        $this->assertSame(1, $kpis['pendencias_documentacao']); // p2 sem CPF
    }

    public function test_metas_por_acao(): void
    {
        $metas = $this->payload()['metas_por_acao'];

        $this->assertCount(1, $metas);
        $this->assertSame('Formação Inicial', $metas[0]['acao']);
        $this->assertSame(20, $metas[0]['previstas']); // 10 + 10
        $this->assertSame(2, $metas[0]['presentes']);
        $this->assertSame(1, $metas[0]['avaliacoes']);
        $this->assertSame(3, $metas[0]['inscritos']); // p1, p2, p3
        $this->assertSame(10.0, $metas[0]['pct_realizado']); // 2/20
    }

    public function test_participacao_por_regiao(): void
    {
        $regioes = $this->payload()['participacao_por_regiao'];

        $nordeste = collect($regioes)->firstWhere('regiao', 'Nordeste');
        $this->assertNotNull($nordeste);
        $this->assertSame(20, $nordeste['previstas']);
        $this->assertSame(2, $nordeste['presentes']);
    }

    public function test_segmentos(): void
    {
        $segmentos = collect($this->payload()['segmentos']);

        $this->assertSame(1, $segmentos->firstWhere('segmento', 'Rede de Ensino')['presentes']);
        // p3 (Movimento Social) está ausente, então não aparece entre presentes.
        $this->assertNull($segmentos->firstWhere('segmento', 'Movimento Social'));
    }

    public function test_evolucao_semestral(): void
    {
        $evolucao = $this->payload()['evolucao_semestral'];

        $this->assertCount(1, $evolucao);
        $this->assertSame('2026-S1', $evolucao[0]['semestre']);
        $this->assertSame(2, $evolucao[0]['presentes']);
        $this->assertSame(1, $evolucao[0]['avaliacoes']);
    }

    public function test_municipios_baixo_engajamento(): void
    {
        // presentes 2 / previstas 20 = 10% < 50% (limite padrão)
        $baixo = $this->payload()['municipios_baixo_engajamento'];

        $this->assertCount(1, $baixo);
        $this->assertSame('Recife', $baixo[0]['municipio']);
        $this->assertSame(10.0, $baixo[0]['pct_realizado']);
    }

    public function test_recorrencia_ausencia(): void
    {
        $ausencias = $this->payload()['recorrencia_ausencia'];

        $this->assertCount(1, $ausencias); // apenas p3, com 2 ausências
        $this->assertSame(2, $ausencias[0]['ausencias']);
    }

    public function test_eventos_sem_avaliacao(): void
    {
        // a1 teve avaliação respondida; a2 (passada) não teve nenhuma presença/avaliação.
        $sem = $this->payload()['eventos_sem_avaliacao'];

        $this->assertCount(1, $sem);
        $this->assertSame('Formação Inicial', $sem[0]['acao']);
    }

    public function test_filtro_por_regiao_inexistente_zera_resultados(): void
    {
        $kpis = $this->payload(['regiao_id' => 999999])['kpis'];

        $this->assertSame(0, $kpis['participantes_totais']);
        $this->assertSame(0, $kpis['municipios_ativos']);
    }
}
