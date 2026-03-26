@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h4 fw-bold text-engaja mb-0">Momentos — {{ $evento->nome }}</h1>

        @hasanyrole('administrador|formador')
        <small class="text-muted">Gerencie a programação da ação pedagógica</small>
        @endhasanyrole
      </div>

      @hasanyrole('administrador|formador')
      <a href="{{ route('eventos.atividades.create', $evento) }}" class="btn btn-engaja">+ Novo momento</a>
      @endhasanyrole
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Dia</th>
            <th>Hora início</th>
            <th>Hora de término</th>
            <th>Municípios</th>
            <th>Público esperado</th>
            <th>Carga horária</th>
            <th>Status</th>
            @auth
            <th class="text-end">Ações</th>
            @endauth
          </tr>
        </thead>
        @php $temPermissao = auth()->check(); @endphp
        <tbody>
          @forelse($atividades as $at)
            @php
              $munLabel = $at->municipios->isNotEmpty()
                ? $at->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
                : '-';
            @endphp
            <tr>
              <td>{{ \Carbon\Carbon::parse($at->dia)->format('d/m/Y') }}</td>
              <td>{{ \Carbon\Carbon::parse($at->hora_inicio)->format('H:i') }}</td>
              <td>{{ $at->hora_fim ? \Carbon\Carbon::parse($at->hora_fim)->format('H:i') : '—' }}</td>
              <td>{{ $munLabel }}</td>
              <td>{{ $at->publico_esperado ? number_format($at->publico_esperado, 0, ',', '.') : '—' }}</td>
              <td>
                @php
                  $carga = $at->carga_horaria;
                  $cargaLabel = !is_null($carga) ? number_format($carga, 0, ',', '.') . 'h' : '—';
                @endphp
                {{ $cargaLabel }}
              </td>
              <td>
                {{-- ⚠️ Badge checklist incompleto --}}
                @if($at->checklists_incompletos)
                  <button type="button"
                          class="badge bg-warning text-dark border-0 btn-checklist-reabrir"
                          data-atividade-id="{{ $at->id }}"
                          data-checklist-pl="{{ json_encode($at->checklist_planejamento ?? []) }}"
                          data-checklist-en="{{ json_encode($at->checklist_encerramento ?? []) }}"
                          style="cursor:pointer; font-size:0.75rem; padding:0.35rem 0.5rem;">
                    ⚠️ Checklist incompleto
                  </button>
                @endif
              </td>
              @auth
              <td class="text-end text-nowrap">
                @php $minhaAvaliacaoAtividade = $at->minha_avaliacao_atividade; @endphp
                <a href="{{ route('atividades.show', $at) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                <a href="{{ route('atividades.edit', $at) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                 <a href="{{ $minhaAvaliacaoAtividade
                        ? route('avaliacao-atividade.edit',   $at) 
                        : route('avaliacao-atividade.create', $at) }}"
                   class="btn btn-sm {{ $minhaAvaliacaoAtividade ? 'btn-warning' : 'btn-outline-warning' }}"
                   title="{{ $minhaAvaliacaoAtividade ? 'Editar meu relatório' : 'Criar meu relatório' }}">
                   📋 {{ $minhaAvaliacaoAtividade ? 'Meu relatório' : 'Criar relatório' }}
                </a>

                <form class="d-inline" method="POST" action="{{ route('atividades.destroy', $at) }}"
                  data-confirm="Tem certeza que deseja excluir este momento?">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
              </td>
              @endauth
            </tr>
          @empty
            <tr>
              <td colspan="{{ $temPermissao ? 8 : 7 }}" class="text-center text-muted py-4">Nenhum momento cadastrado.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{ $atividades->links() }}

    <div class="mt-3">
      <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar à ação pedagógica</a>
    </div>
  </div>

  {{-- Modal de reabertura de checklist --}}
  <div class="modal fade" id="modalReopenChecklist" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header bg-engaja text-white border-0">
          <h5 class="modal-title fw-bold">⚠️ Checklist Incompleto</h5>
        </div>
        <div class="modal-body" id="reopen-checklist-body">
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-engaja" id="btn-salvar-checklist-reopen">Salvar progresso</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
      const ITENS_PLANEJAMENTO = [
          'Ao planejar cada ação, recorri aos objetivos gerais do projeto, em diálogo com os dados da Leitura do Mundo?',
          'Ao planejar, estabeleci conexão com as outras ações do projeto? (Ex: Cartas para Esperançar, Semear Palavras)',
          'Preparei listas de presença impressas de acordo com os dados a serem inseridos no sistema ENGAJA?',
          'Preparei formulários de avaliação de cada ação de formação, para medir os impactos?',
          'Organizei a lista de materiais necessários e apresentei à coordenação com antecedência?',
          'Organizei a demanda de infraestrutura local com antecedência?',
          'A inscrição do público esperado na formação foi feita?',
          'A informação sobre o dia e horário chegou com antecedência aos públicos participantes?',
          'Os materiais institucionais do projeto para entregar aos participantes estão organizados?',
          'Equipe Pedagógica e Educadores estão com clareza de quem fará o que durante os encontros?',
          'Planejei os momentos de registros audiovisual de cada ação?',
          'Sei como nomear os arquivos e o local onde compartilhar os registros processuais?',
          'Estou de posse de todos os contatos estratégicos em caso de necessidade?'
      ];
      const ITENS_ENCERRAMENTO = [
          'Verifiquei se os municípios estão corretos?',
          'Confirmei a carga horária e os horários de início e término?',
          'O público esperado e os dados do momento estão preenchidos corretamente?'
      ];

      let atividadeIdAtual = null;

      document.querySelectorAll('.btn-checklist-reabrir').forEach(btn => {
          btn.addEventListener('click', function () {
              atividadeIdAtual = this.dataset.atividadeId;
            const marcadosPl = normalizeMarkedIndexes(ITENS_PLANEJAMENTO, JSON.parse(this.dataset.checklistPl || '[]'));
            const marcadosEn = normalizeMarkedIndexes(ITENS_ENCERRAMENTO, JSON.parse(this.dataset.checklistEn || '[]'));

              const body = document.getElementById('reopen-checklist-body');
              body.innerHTML = renderChecklist('planejamento', ITENS_PLANEJAMENTO, marcadosPl)
                             + renderChecklist('encerramento', ITENS_ENCERRAMENTO, marcadosEn);

              new bootstrap.Modal(document.getElementById('modalReopenChecklist')).show();
          });
      });

        function normalizeMarkedIndexes(itens, marcados) {
          if (!Array.isArray(marcados)) {
            return [];
          }

          const normalizedTextToIndex = new Map(
            itens.map((item, index) => [String(item).trim().toLowerCase(), index])
          );

          return [...new Set(
            marcados
              .map((valor) => {
                if (Number.isInteger(valor)) {
                  return valor;
                }

                const asNumber = Number(valor);
                if (Number.isInteger(asNumber)) {
                  return asNumber;
                }

                const textKey = String(valor).trim().toLowerCase();
                return normalizedTextToIndex.has(textKey) ? normalizedTextToIndex.get(textKey) : null;
              })
              .filter((index) => Number.isInteger(index) && index >= 0 && index < itens.length)
          )];
        }

      function renderChecklist(tipo, itens, marcados) {
          let html = `<h6 class="fw-bold mt-2" style="color: #421944;">${tipo === 'planejamento' ? '📋 Planejamento' : '✅ Encerramento'}</h6><div class="vstack gap-2 mb-4">`;
          itens.forEach((item, i) => {
              const checked = marcados.includes(i) ? 'checked' : '';
              html += `<label class="checklist-card d-flex align-items-center gap-3 ${checked ? 'checked' : ''}" style="cursor:pointer;border:2px solid #dee2e6;border-radius:10px;padding:12px 16px; ${checked ? 'background-color: #421944; color: #fff; border-color: #421944;' : ''}">
                  <input type="checkbox" class="js-reopen-item" data-tipo="${tipo}" data-index="${i}" ${checked} style="display:none">
                  <span class="checklist-check-icon" style="width:22px;height:22px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#421944;font-weight:900;opacity:${checked ? 1 : 0}">✓</span>
                  <span>${item}</span>
              </label>`;
          });
          html += '</div>';
          return html;
      }

      document.getElementById('reopen-checklist-body')?.addEventListener('change', function(e) {
          if (e.target.classList.contains('js-reopen-item')) {
              const label = e.target.closest('label');
              label.classList.toggle('checked', e.target.checked);
              label.style.backgroundColor = e.target.checked ? '#421944' : '';
              label.style.color = e.target.checked ? '#fff' : '';
              label.style.borderColor = e.target.checked ? '#421944' : '#dee2e6';
              label.querySelector('.checklist-check-icon').style.opacity = e.target.checked ? 1 : 0;
          }
      });

      document.getElementById('btn-salvar-checklist-reopen')?.addEventListener('click', function () {
          if (!atividadeIdAtual) return;
          const pl = [...document.querySelectorAll('.js-reopen-item[data-tipo="planejamento"]:checked')].map(c => parseInt(c.dataset.index));
          const en = [...document.querySelectorAll('.js-reopen-item[data-tipo="encerramento"]:checked')].map(c => parseInt(c.dataset.index));

          const salvar = (tipo, itens) => fetch(`/atividades/${atividadeIdAtual}/checklist`, {
              method: 'POST',
              headers: { 
                  'Content-Type': 'application/json', 
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
              },
              body: JSON.stringify({ tipo, itens })
          });

          Promise.all([salvar('planejamento', pl), salvar('encerramento', en)])
              .then(() => { window.location.reload(); })
              .catch(() => alert('Erro ao salvar. Tente novamente.'));
      });
  });
</script>
@endpush