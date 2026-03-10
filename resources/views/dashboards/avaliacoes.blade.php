@extends('layouts.app')

@section('content')
<div class="container py-4" id="avaliacoes-dashboard" data-endpoint="{{ route('dashboards.avaliacoes.data', request()->only(['fonte', 'survey_id'])) }}">
  <div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <p class="text-uppercase small text-muted mb-1">Dashboards</p>
        <h1 class="h3 fw-bold mb-1">Respostas dos formularios</h1>
        <p class="text-muted mb-0">Visual limpo, na paleta do projeto, com filtros instantaneos.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hub de dashboards</a>
        <a href="{{ route('dashboards.bi') }}" class="btn btn-outline-secondary">Ir para BI</a>
        <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-primary">Ir para presencas</a>
      </div>
    </div>
  </div>
  @if(request('fonte') === 'limesurvey')
    <div class="alert alert-info border-0 shadow-sm py-2">
      Fonte ativa: <strong>LimeSurvey</strong>
      @if(request('survey_id'))
        (survey_id={{ request('survey_id') }})
      @endif
    </div>
  @endif

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

  <div class="row g-3 mb-3" id="bi-matriz-section" style="display:none;">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 fw-bold mb-0">BI por municipio (questoes matriz)</h2>
        <span class="badge bg-success-subtle text-success">LimeSurvey</span>
      </div>
      <div id="bi-matriz-container"></div>
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
  const biMatrizSection = document.getElementById('bi-matriz-section');
  const biMatrizContainer = document.getElementById('bi-matriz-container');
  const refreshBtn = document.getElementById('btn-recarregar');
  const chartInstances = new Map();
  const chartPreferences = new Map();
  const matrixBlockState = new Map();
  let cachedQuestionBlocks = [];
  let cachedPerguntas = [];
  let cachedBiMatrizes = [];
  let biMatrizChart = null;
  const biMatrizState = {
    matrizCodigo: null,
    linhaCodigo: null,
    medida: null,
  };
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

  function renderQuestionBlocks(blocks) {
    const lista = Array.isArray(blocks) ? blocks : [];
    cachedQuestionBlocks = lista;
    if (biMatrizSection) {
      biMatrizSection.style.display = 'none';
    }

    cardsQuestoes.innerHTML = '';
    if (lista.length === 0) {
      cardsQuestoes.innerHTML = `
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-muted text-center">Sem respostas para os filtros aplicados.</div>
          </div>
        </div>`;
      return;
    }

    lista.forEach((block) => {
      if (block?.kind === 'matrix' && block?.matrix) {
        renderMatrixBlockCard(block);
      } else if (block?.kind === 'simple' && block?.question) {
        renderSimpleQuestionCard(block.question, block.title || block.question?.texto);
      }
    });
  }

  function renderSimpleQuestionCard(pergunta, titleOverride = null) {
    const totalRespostas = pergunta.total || 0;
    const titulo = cleanText(titleOverride || pergunta.texto);
    const resumo = cleanText(pergunta.resumo || '');

    const wrapper = document.createElement('div');
    wrapper.className = 'col-12';
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

    const isMunicipioSeries = pergunta.tipo === 'municipio_series' && Array.isArray(pergunta.municipio_series);
    const isText = pergunta.tipo === 'texto';
    const respostas = Array.isArray(pergunta.respostas) ? pergunta.respostas : [];
    const exemplos = Array.isArray(pergunta.exemplos) ? pergunta.exemplos : [];

    if (isMunicipioSeries) {
      const canvas = document.createElement('canvas');
      canvas.height = 120;
      body.appendChild(canvas);

      if (chartInstances.has(pergunta.id)) {
        chartInstances.get(pergunta.id).destroy();
      }

      const labels = (pergunta.municipio_labels || []).map((label) => cleanText(label));
      const datasets = (pergunta.municipio_series || []).map((serie, idx) => ({
        label: cleanText(serie.label || serie.code || `Serie ${idx + 1}`),
        data: Array.isArray(serie.data) ? serie.data.map((n) => Number(n || 0)) : [],
        backgroundColor: palette[idx % palette.length],
        borderColor: palette[idx % palette.length],
      }));

      const chart = new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
          responsive: true,
          plugins: { legend: { display: true } },
          scales: {
            x: { stacked: true, ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
            y: { stacked: true, ticks: { color: '#64748b', precision: 0 } },
          },
        },
      });
      chartInstances.set(pergunta.id, chart);
    } else if (isText) {
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
          if (value === 'auto') chartPreferences.delete(pergunta.id);
          else chartPreferences.set(pergunta.id, value);
          renderQuestionBlocks(cachedQuestionBlocks);
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
      if (baseChartType === 'doughnut' || baseChartType === 'polarArea') delete options.scales;
      const autoHorizontal = !userPref && baseChartType === 'bar' && labels.length > 4;
      if (baseChartType === 'bar' && (chartType === 'bar-horizontal' || autoHorizontal)) options.indexAxis = 'y';

      const chart = new Chart(canvas, { type: baseChartType, data, options });
      chartInstances.set(pergunta.id, chart);
    }

    wrapper.appendChild(card);
    cardsQuestoes.appendChild(wrapper);
  }

  function renderMatrixBlockCard(block) {
    const matriz = block.matrix;
    const blockId = `matrix-${block.id}`;
    if (!matrixBlockState.has(blockId)) {
      matrixBlockState.set(blockId, { linhaCodigo: '__ALL__', medida: (matriz.medidas || [])[0] || null, chartType: 'bar' });
    }
    const state = matrixBlockState.get(blockId);

    const wrapper = document.createElement('div');
    wrapper.className = 'col-12';
    const card = document.createElement('div');
    card.className = 'card border-0 shadow-sm h-100';
    card.innerHTML = `
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2 question-header">
          <div>
            <div class="fw-bold">${cleanText(block.title || matriz.texto || block.id)}</div>
            <small class="text-muted">Questao matriz</small>
          </div>
          <div class="d-flex align-items-start gap-2 controls-slot question-controls"></div>
        </div>
        <div class="question-body mt-2"></div>
      </div>
    `;
    const body = card.querySelector('.question-body');
    const controls = card.querySelector('.controls-slot');

    const linhaSelect = document.createElement('select');
    linhaSelect.className = 'form-select form-select-sm';
    linhaSelect.style.minWidth = '220px';
    const optAll = document.createElement('option');
    optAll.value = '__ALL__';
    optAll.textContent = 'Todas as subquestoes';
    linhaSelect.appendChild(optAll);
    (matriz.linhas || []).forEach((linha) => {
      const option = document.createElement('option');
      option.value = linha.codigo;
      option.textContent = cleanText(linha.label || linha.codigo);
      linhaSelect.appendChild(option);
    });
    linhaSelect.value = state.linhaCodigo || '__ALL__';

    const medidaSelect = document.createElement('select');
    medidaSelect.className = 'form-select form-select-sm';
    medidaSelect.style.minWidth = '170px';
    (matriz.medidas || []).forEach((medida) => {
      const option = document.createElement('option');
      option.value = medida;
      option.textContent = cleanText(medida);
      medidaSelect.appendChild(option);
    });
    medidaSelect.value = state.medida || ((matriz.medidas || [])[0] || '');

    const tipoSelect = document.createElement('select');
    tipoSelect.className = 'form-select form-select-sm';
    tipoSelect.style.minWidth = '150px';
    [
      ['bar', 'Barras'],
      ['line', 'Linha'],
    ].forEach(([value, label]) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = label;
      tipoSelect.appendChild(option);
    });
    tipoSelect.value = state.chartType || 'bar';

    controls.appendChild(linhaSelect);
    controls.appendChild(medidaSelect);
    controls.appendChild(tipoSelect);

    const meta = document.createElement('div');
    meta.className = 'text-muted small mb-2';
    body.appendChild(meta);
    const canvas = document.createElement('canvas');
    canvas.height = 120;
    body.appendChild(canvas);

    const draw = () => {
      state.linhaCodigo = linhaSelect.value;
      state.medida = medidaSelect.value;
      state.chartType = tipoSelect.value;
      matrixBlockState.set(blockId, state);

      let yearsMap = {};
      if (state.linhaCodigo === '__ALL__') {
        const linhas = (matriz.linhas || []).map((item) => item.codigo);
        linhas.forEach((codigoLinha) => {
          const byYear = matriz?.valores?.[codigoLinha]?.[state.medida] || {};
          Object.entries(byYear).forEach(([ano, byMunicipio]) => {
            if (!yearsMap[ano]) yearsMap[ano] = {};
            Object.entries(byMunicipio || {}).forEach(([municipio, valor]) => {
              yearsMap[ano][municipio] = Number(yearsMap[ano][municipio] || 0) + Number(valor || 0);
            });
          });
        });
      } else {
        yearsMap = matriz?.valores?.[state.linhaCodigo]?.[state.medida] || {};
      }

      const anos = (matriz.anos || []).filter((ano) => Object.prototype.hasOwnProperty.call(yearsMap, ano));
      const municipios = Array.from(new Set(anos.flatMap((ano) => Object.keys(yearsMap[ano] || {}))))
        .sort((a, b) => a.localeCompare(b, 'pt-BR', { sensitivity: 'base' }));

      if (chartInstances.has(blockId)) {
        chartInstances.get(blockId).destroy();
      }

      if (!anos.length || !municipios.length) {
        meta.textContent = 'Sem dados para os filtros selecionados.';
        return;
      }

      const datasets = anos.map((ano, idx) => ({
        label: ano,
        data: municipios.map((m) => Number((yearsMap[ano] || {})[m] || 0)),
        backgroundColor: palette[idx % palette.length],
        borderColor: palette[idx % palette.length],
        tension: 0.2,
      }));

      const chart = new Chart(canvas, {
        type: state.chartType,
        data: { labels: municipios.map(cleanText), datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            title: {
              display: true,
              text: `${state.linhaCodigo === '__ALL__' ? 'Todas as subquestoes' : cleanText((matriz.linhas || []).find((item) => item.codigo === state.linhaCodigo)?.label || state.linhaCodigo)} - ${cleanText(state.medida)}`,
            },
          },
          scales: {
            x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
            y: { ticks: { color: '#64748b', precision: 0 } },
          },
        },
      });
      chartInstances.set(blockId, chart);
      meta.textContent = `Campo de municipio: ${matriz.municipio_field || 'nao identificado'} | Municipios exibidos: ${municipios.length}`;
    };

    linhaSelect.addEventListener('change', draw);
    medidaSelect.addEventListener('change', draw);
    tipoSelect.addEventListener('change', draw);
    draw();

    wrapper.appendChild(card);
    cardsQuestoes.appendChild(wrapper);
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

  function renderBiMatrizes(matrizes) {
    cachedBiMatrizes = Array.isArray(matrizes) ? matrizes : [];

    if (!biMatrizSection || !biMatrizContainer) return;

    if (!cachedBiMatrizes.length) {
      biMatrizSection.style.display = 'none';
      biMatrizContainer.innerHTML = '';
      if (biMatrizChart) {
        biMatrizChart.destroy();
        biMatrizChart = null;
      }
      return;
    }

    biMatrizSection.style.display = '';
    biMatrizContainer.innerHTML = `
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-5 col-md-6">
              <label class="form-label text-muted small mb-1">Questao matriz</label>
              <select class="form-select form-select-sm" id="bi-matriz-select"></select>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label text-muted small mb-1">Subquestao (linha)</label>
              <select class="form-select form-select-sm" id="bi-matriz-linha"></select>
            </div>
            <div class="col-lg-4 col-md-6">
              <label class="form-label text-muted small mb-1">Medida</label>
              <select class="form-select form-select-sm" id="bi-matriz-medida"></select>
            </div>
          </div>
          <div class="text-muted small mb-2" id="bi-matriz-meta"></div>
          <canvas id="bi-matriz-canvas" height="120"></canvas>
        </div>
      </div>
    `;

    const matrizSelect = biMatrizContainer.querySelector('#bi-matriz-select');
    const linhaSelect = biMatrizContainer.querySelector('#bi-matriz-linha');
    const medidaSelect = biMatrizContainer.querySelector('#bi-matriz-medida');
    const canvas = biMatrizContainer.querySelector('#bi-matriz-canvas');
    const metaEl = biMatrizContainer.querySelector('#bi-matriz-meta');

    cachedBiMatrizes.forEach((matriz) => {
      const option = document.createElement('option');
      option.value = matriz.codigo;
      option.textContent = `${matriz.codigo} - ${cleanText(matriz.texto || matriz.codigo)}`;
      matrizSelect.appendChild(option);
    });

    const firstMatriz = cachedBiMatrizes[0];
    biMatrizState.matrizCodigo = biMatrizState.matrizCodigo || firstMatriz.codigo;
    if (!cachedBiMatrizes.some((item) => item.codigo === biMatrizState.matrizCodigo)) {
      biMatrizState.matrizCodigo = firstMatriz.codigo;
    }
    matrizSelect.value = biMatrizState.matrizCodigo;

    function matrizAtual() {
      return cachedBiMatrizes.find((item) => item.codigo === matrizSelect.value) || null;
    }

    function popularFiltrosMatriz() {
      const matriz = matrizAtual();
      if (!matriz) return;

      linhaSelect.innerHTML = '';
      const optionTodos = document.createElement('option');
      optionTodos.value = '__ALL__';
      optionTodos.textContent = 'Todos';
      linhaSelect.appendChild(optionTodos);

      (matriz.linhas || []).forEach((linha) => {
        const option = document.createElement('option');
        option.value = linha.codigo;
        option.textContent = cleanText(linha.label || linha.codigo);
        linhaSelect.appendChild(option);
      });

      medidaSelect.innerHTML = '';
      (matriz.medidas || []).forEach((medida) => {
        const option = document.createElement('option');
        option.value = medida;
        option.textContent = cleanText(medida);
        medidaSelect.appendChild(option);
      });

      const linhaDisponivel = (matriz.linhas || []).map((l) => l.codigo);
      const medidaDisponivel = (matriz.medidas || []);

      if (biMatrizState.linhaCodigo !== '__ALL__' && !linhaDisponivel.includes(biMatrizState.linhaCodigo)) {
        biMatrizState.linhaCodigo = linhaDisponivel[0] || null;
      }
      if (!medidaDisponivel.includes(biMatrizState.medida)) {
        biMatrizState.medida = medidaDisponivel[0] || null;
      }

      if (!biMatrizState.linhaCodigo) {
        biMatrizState.linhaCodigo = '__ALL__';
      }
      linhaSelect.value = biMatrizState.linhaCodigo;
      if (biMatrizState.medida) medidaSelect.value = biMatrizState.medida;
    }

    function renderGraficoMatriz() {
      const matriz = matrizAtual();
      if (!matriz || !canvas) return;

      const linha = linhaSelect.value;
      const medida = medidaSelect.value;
      biMatrizState.matrizCodigo = matriz.codigo;
      biMatrizState.linhaCodigo = linha;
      biMatrizState.medida = medida;

      let yearsMap = {};
      if (linha === '__ALL__') {
        const linhas = (matriz.linhas || []).map((item) => item.codigo);
        linhas.forEach((codigoLinha) => {
          const byYear = matriz?.valores?.[codigoLinha]?.[medida] || {};
          Object.entries(byYear).forEach(([ano, byMunicipio]) => {
            if (!yearsMap[ano]) yearsMap[ano] = {};
            Object.entries(byMunicipio || {}).forEach(([municipio, valor]) => {
              const atual = Number(yearsMap[ano][municipio] || 0);
              yearsMap[ano][municipio] = atual + Number(valor || 0);
            });
          });
        });
      } else {
        yearsMap = matriz?.valores?.[linha]?.[medida] || {};
      }

      const anos = (matriz.anos || []).filter((ano) => Object.prototype.hasOwnProperty.call(yearsMap, ano));
      if (!anos.length) {
        if (biMatrizChart) {
          biMatrizChart.destroy();
          biMatrizChart = null;
        }
        metaEl.textContent = 'Sem dados para os filtros selecionados.';
        return;
      }

      const municipiosSet = new Set();
      anos.forEach((ano) => {
        const byMunicipio = yearsMap[ano] || {};
        Object.keys(byMunicipio).forEach((municipio) => municipiosSet.add(municipio));
      });

      let municipios = Array.from(municipiosSet);
      municipios.sort((a, b) => a.localeCompare(b, 'pt-BR', { sensitivity: 'base' }));

      const datasets = anos.map((ano, idx) => ({
        label: ano,
        data: municipios.map((municipio) => Number((yearsMap[ano] || {})[municipio] || 0)),
        backgroundColor: palette[idx % palette.length],
      }));

      if (biMatrizChart) {
        biMatrizChart.destroy();
      }

      biMatrizChart = new Chart(canvas, {
        type: 'bar',
        data: { labels: municipios.map((m) => cleanText(m)), datasets },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            title: {
              display: true,
              text: `${linha === '__ALL__' ? 'Todas as subquestoes' : cleanText((matriz.linhas || []).find((item) => item.codigo === linha)?.label || linha)} - ${cleanText(medida)}`,
            },
          },
          scales: {
            x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
            y: { ticks: { color: '#64748b', precision: 0 } },
          },
        },
      });

      metaEl.textContent = `Campo de municipio: ${matriz.municipio_field || 'nao identificado'} | Municipios exibidos: ${municipios.length}`;
    }

    matrizSelect.addEventListener('change', () => {
      popularFiltrosMatriz();
      renderGraficoMatriz();
    });
    linhaSelect.addEventListener('change', renderGraficoMatriz);
    medidaSelect.addEventListener('change', renderGraficoMatriz);

    popularFiltrosMatriz();
    renderGraficoMatriz();
  }

  async function loadData() {
    setLoading(true);
    try {
      const url = `${endpoint}?${buildParams()}`;
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.erro || 'Erro ao carregar dados.');
      }

      renderTotals(payload.totais || {});
      if (Array.isArray(payload.question_blocks) && payload.question_blocks.length > 0) {
        renderQuestionBlocks(payload.question_blocks);
      } else {
        renderBiMatrizes(payload.bi_matrizes || []);
        renderCharts(payload.perguntas || []);
      }
    } catch (error) {
      const mensagem = (error && error.message) ? error.message : 'Erro ao carregar dados.';
      renderBiMatrizes([]);
      cardsQuestoes.innerHTML = `<div class=\"card border-0 shadow-sm\"><div class=\"card-body text-danger\">${mensagem}</div></div>`;
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
