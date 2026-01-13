@extends('layouts.app')

@section('content')
<div class="container py-4" id="avaliacoes-dashboard" data-endpoint="{{ route('dashboards.avaliacoes.data') }}">
  <div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <p class="text-uppercase small text-muted mb-1">Dashboards</p>
        <h1 class="h3 fw-bold mb-1">Respostas dos formularios</h1>
        <p class="text-muted mb-0">Visual limpo, na paleta do projeto, com filtros instantaneos.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hub de dashboards</a>
        <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-primary">Ir para presencas</a>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Modelo</label>
          <select class="form-select js-filter" id="f-template">
            <option value="">Todos</option>
            @foreach($templates as $template)
            <option value="{{ $template->id }}" @selected(request('template_id') == $template->id)>{{ $template->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Evento</label>
          <select class="form-select js-filter" id="f-evento">
            <option value="">Todos</option>
            @foreach($eventos as $evento)
            <option value="{{ $evento->id }}">{{ $evento->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Atividade / momento</label>
          <select class="form-select js-filter" id="f-atividade">
            <option value="">Todas</option>
            @foreach($atividades as $atividade)
            @php
              $diaFormatado = $atividade->dia ? \Illuminate\Support\Carbon::parse($atividade->dia)->format('d/m') : '';
            @endphp
            <option value="{{ $atividade->id }}">
              {{ $atividade->descricao ?? 'Momento' }} - {{ $diaFormatado }} {{ $atividade->hora_inicio }}
              @if($atividade->evento) ({{ $atividade->evento->nome }}) @endif
            </option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">De</label>
              <input type="date" class="form-control js-filter" id="f-de">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Ate</label>
              <input type="date" class="form-control js-filter" id="f-ate">
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <button class="btn btn-primary" id="btn-recarregar">
          Atualizar agora
        </button>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3" id="cards-totais">
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Submissoes</p>
          <div class="h3 fw-bold mb-0" data-total="submissoes">0</div>
          <small class="text-muted">Respostas completas registradas</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Questoes</p>
          <div class="h3 fw-bold mb-0" data-total="questoes">0</div>
          <small class="text-muted">Com alguma resposta</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Eventos</p>
          <div class="h3 fw-bold mb-0" data-total="eventos">0</div>
          <small class="text-muted">Com respostas vinculadas</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Ultima resposta</p>
          <div class="h3 fw-bold mb-0" data-total="ultima">-</div>
          <small class="text-muted">Horario da ultima entrada</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 fw-bold mb-0">Distribuicao por questao</h2>
        <span class="badge bg-primary-subtle text-primary">Interativo</span>
      </div>
      <div class="row g-3" id="cards-questoes">
        <div class="col-12" id="placeholder-card">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted">
              Carregando graficos...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="textAnswersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title js-text-modal-title">Respostas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted small mb-2 js-text-modal-count"></div>
        <div class="vstack gap-2 js-text-modal-list" style="max-height: 60vh; overflow: auto;"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
  @media (max-width: 576px) {
    #cards-questoes .question-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    #cards-questoes .question-controls {
      width: 100%;
      justify-content: flex-start;
    }
    #cards-questoes .question-controls select {
      width: 100%;
      max-width: none;
    }
  }
</style>
<script>
(() => {
  const container = document.getElementById('avaliacoes-dashboard');
  if (!container) return;

  const endpoint = container.dataset.endpoint;
  const filters = {
    template: document.getElementById('f-template'),
    evento: document.getElementById('f-evento'),
    atividade: document.getElementById('f-atividade'),
    de: document.getElementById('f-de'),
    ate: document.getElementById('f-ate'),
  };
  const totalsEls = {
    submissoes: document.querySelector('[data-total=\"submissoes\"]'),
    questoes: document.querySelector('[data-total=\"questoes\"]'),
    eventos: document.querySelector('[data-total=\"eventos\"]'),
    ultima: document.querySelector('[data-total=\"ultima\"]'),
  };
  const cardsQuestoes = document.getElementById('cards-questoes');
  const refreshBtn = document.getElementById('btn-recarregar');
  const chartInstances = new Map();
  const chartPreferences = new Map();
  let cachedPerguntas = [];
  const textModalEl = document.getElementById('textAnswersModal');
  const textModalTitle = textModalEl?.querySelector('.js-text-modal-title');
  const textModalList = textModalEl?.querySelector('.js-text-modal-list');
  const textModalCount = textModalEl?.querySelector('.js-text-modal-count');
  let textModalInstance = null;
  const palette = ['#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D', '#601F69', '#6C345E', '#9602C7', '#A95DB1', '#D9A8E2', '#ECDEEC'];

  function buildParams() {
    const params = new URLSearchParams();
    if (filters.template.value) params.set('template_id', filters.template.value);
    if (filters.evento.value) params.set('evento_id', filters.evento.value);
    if (filters.atividade.value) params.set('atividade_id', filters.atividade.value);
    if (filters.de.value) params.set('de', filters.de.value);
    if (filters.ate.value) params.set('ate', filters.ate.value);
    return params.toString();
  }

  function setLoading(state) {
    if (state) {
      cardsQuestoes.innerHTML = `
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted">Carregando graficos...</div>
          </div>
        </div>`;
    }
  }

  function cleanText(value) {
    if (!value) return '';
    const text = String(value).replace(/<[^>]+>/g, ' ');
    return text.replace(/\s+/g, ' ').trim();
  }

  function openTextModal(pergunta, respostas) {
    const lista = Array.isArray(respostas) ? respostas : [];
    const titulo = cleanText(pergunta?.texto || 'Respostas');
    const total = lista.length;

    if (!textModalEl || !window.bootstrap?.Modal) {
      const texto = lista.length ? lista.map((resp) => `- ${cleanText(resp)}`).join('\n') : 'Sem respostas abertas.';
      alert(`${titulo}\n\n${texto}`);
      return;
    }

    if (!textModalInstance) {
      textModalInstance = new window.bootstrap.Modal(textModalEl);
    }

    if (textModalTitle) {
      textModalTitle.textContent = titulo;
    }

    if (textModalCount) {
      textModalCount.textContent = `${total} resposta(s)`;
    }

    if (textModalList) {
      textModalList.innerHTML = '';
      if (total === 0) {
        textModalList.innerHTML = '<div class=\"text-muted\">Sem respostas abertas.</div>';
      } else {
        lista.forEach((resp) => {
          const item = document.createElement('div');
          item.className = 'p-2 rounded border bg-light';
          item.textContent = cleanText(resp);
          textModalList.appendChild(item);
        });
      }
    }

    textModalInstance.show();
  }

  function renderTotals(totais) {
    totalsEls.submissoes.textContent = new Intl.NumberFormat('pt-BR').format(totais.submissoes || 0);
    totalsEls.questoes.textContent = new Intl.NumberFormat('pt-BR').format(totais.questoes || 0);
    totalsEls.eventos.textContent = new Intl.NumberFormat('pt-BR').format(totais.eventos || 0);
    totalsEls.ultima.textContent = totais.ultima || '-';
  }

  function resolveChartType(pergunta, labels) {
    const userPref = chartPreferences.get(pergunta.id);
    if (userPref && userPref !== 'auto') return userPref;

    if (pergunta.tipo === 'boolean') return 'doughnut';
    if (pergunta.tipo === 'numero') return 'line';
    if (pergunta.tipo === 'escala') return 'bar';
    return labels.length > 3 ? 'polarArea' : 'bar';
  }

  function renderCharts(perguntas) {
    cachedPerguntas = perguntas;
    cardsQuestoes.innerHTML = '';
    if (!perguntas || perguntas.length === 0) {
      cardsQuestoes.innerHTML = `
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-muted text-center">Sem respostas para os filtros aplicados.</div>
          </div>
        </div>`;
      return;
    }

    perguntas.forEach((pergunta) => {
      const totalRespostas = pergunta.total || 0;
      const titulo = cleanText(pergunta.texto);
      const resumo = cleanText(pergunta.resumo || '');

      const wrapper = document.createElement('div');
      wrapper.className = 'col-12 col-lg-6';
      const card = document.createElement('div');
      card.className = 'card border-0 shadow-sm h-100';
      card.innerHTML = `
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2 question-header">
            <div>
              <div class="fw-bold">${titulo}</div>
              <small class="text-muted">${totalRespostas} resposta(s)</small>
            </div>
            <div class="d-flex align-items-start gap-2 controls-slot question-controls">
              ${resumo ? `<span class="badge bg-primary-subtle text-primary">${resumo}</span>` : ''}
            </div>
          </div>
          <div class="question-body mt-2"></div>
        </div>
      `;
      const body = card.querySelector('.question-body');
      const controlsSlot = card.querySelector('.controls-slot');

      const isText = pergunta.tipo === 'texto';
      const respostas = Array.isArray(pergunta.respostas) ? pergunta.respostas : [];
      const exemplos = Array.isArray(pergunta.exemplos) ? pergunta.exemplos : [];

      if (isText) {
        const listaFonte = respostas.length ? respostas : exemplos;
        const limitePreview = 5;

        const list = document.createElement('div');
        list.className = 'vstack gap-2';

        const itens = listaFonte.slice(0, limitePreview);
        if (itens.length === 0) {
          list.innerHTML = '<div class=\"text-muted\">Sem respostas abertas.</div>';
        } else {
          itens.forEach((resp) => {
            const item = document.createElement('div');
            item.className = 'p-2 rounded border bg-light';
            item.textContent = cleanText(resp);
            list.appendChild(item);
          });
        }

        if (listaFonte.length > limitePreview) {
          const hint = document.createElement('div');
          hint.className = 'text-muted small';
          hint.textContent = `Mostrando ${limitePreview} de ${listaFonte.length} resposta(s)`;
          list.appendChild(hint);
        }

        body.appendChild(list);

        if (listaFonte.length > limitePreview) {
          const toggleBtn = document.createElement('button');
          toggleBtn.type = 'button';
          toggleBtn.className = 'btn btn-outline-primary btn-sm align-self-start mt-1';
          toggleBtn.textContent = `Ver todas as respostas (${listaFonte.length})`;
          toggleBtn.addEventListener('click', () => openTextModal(pergunta, listaFonte));
          body.appendChild(toggleBtn);
        }
      } else {
        const canvas = document.createElement('canvas');
        canvas.height = 120;
        body.appendChild(canvas);

        if (chartInstances.has(pergunta.id)) {
          chartInstances.get(pergunta.id).destroy();
        }

        const labels = (pergunta.labels || []).map((label) => cleanText(label));
        const bg = labels.map((_, idx) => palette[idx % palette.length]);

        const typeOptions = [
          { value: 'auto', label: 'Auto' },
          { value: 'bar', label: 'Barras (vertical)' },
          { value: 'bar-horizontal', label: 'Barras (horizontal)' },
          { value: 'doughnut', label: 'Pizza' },
          { value: 'polarArea', label: 'Polar' },
          { value: 'line', label: 'Linha' },
        ];

        const userPref = chartPreferences.get(pergunta.id);
        const chartType = resolveChartType(pergunta, labels);

        if (controlsSlot) {
          const select = document.createElement('select');
          select.className = 'form-select form-select-sm';
          select.style.minWidth = '150px';
          typeOptions.forEach((opt) => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            select.appendChild(option);
          });
          select.value = userPref || 'auto';
          select.addEventListener('change', (event) => {
            const value = event.target.value;
            if (value === 'auto') {
              chartPreferences.delete(pergunta.id);
            } else {
              chartPreferences.set(pergunta.id, value);
            }
            renderCharts(cachedPerguntas);
          });
          controlsSlot.appendChild(select);
        }

        const baseChartType = chartType === 'bar-horizontal' ? 'bar' : chartType;
        const data = {
          labels,
          datasets: [{
            label: 'Respostas',
            data: pergunta.values,
            backgroundColor: baseChartType === 'line' ? 'rgba(66,25,68,0.15)' : bg,
            borderColor: palette[0],
            tension: 0.2,
            fill: baseChartType === 'line',
          }],
        };

        const options = {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            x: { ticks: { color: '#64748b' } },
            y: { ticks: { color: '#64748b', precision: 0 } },
          },
        };

        if (baseChartType === 'doughnut' || baseChartType === 'polarArea') {
          delete options.scales;
        }

        const autoHorizontal = !userPref && baseChartType === 'bar' && labels.length > 4;
        if (baseChartType === 'bar' && (chartType === 'bar-horizontal' || autoHorizontal)) {
          options.indexAxis = 'y';
        }

        const chart = new Chart(canvas, { type: baseChartType, data, options });
        chartInstances.set(pergunta.id, chart);
      }

      wrapper.appendChild(card);
      cardsQuestoes.appendChild(wrapper);
    });
  }

  async function loadData() {
    setLoading(true);
    try {
      const url = `${endpoint}?${buildParams()}`;
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      const payload = await response.json();

      renderTotals(payload.totais || {});
      renderCharts(payload.perguntas || []);
    } catch (error) {
      cardsQuestoes.innerHTML = '<div class=\"card border-0 shadow-sm\"><div class=\"card-body text-danger\">Erro ao carregar dados.</div></div>';
    }
  }

  document.querySelectorAll('.js-filter').forEach((input) => {
    input.addEventListener('change', loadData);
  });
  refreshBtn.addEventListener('click', loadData);

  loadData();
})();
</script>
@endsection
