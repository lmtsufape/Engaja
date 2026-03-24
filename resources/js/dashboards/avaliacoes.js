import Chart from 'chart.js/auto';

const PALETTE = [
  '#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D',
  '#601F69', '#6C345E', '#9602C7', '#A95DB1', '#D9A8E2', '#ECDEEC',
];

const fmt = new Intl.NumberFormat('pt-BR');

// ─── Helpers ───────────────────────────────────────────────

function cleanText(value) {
  if (!value) return '';
  return String(value).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function bg(index) {
  return PALETTE[index % PALETTE.length];
}

function destroyChart(map, key) {
  if (map.has(key)) {
    map.get(key).destroy();
    map.delete(key);
  }
}

function makeSelect(className, minWidth, options, value, onChange) {
  const select = document.createElement('select');
  select.className = className;
  select.style.minWidth = minWidth;
  options.forEach(([val, label]) => {
    const opt = document.createElement('option');
    opt.value = val;
    opt.textContent = label;
    select.appendChild(opt);
  });
  select.value = value;
  select.addEventListener('change', onChange);
  return select;
}

function createCanvas(parent, height = 120) {
  const canvas = document.createElement('canvas');
  canvas.height = height;
  parent.appendChild(canvas);
  return canvas;
}

function buildCardShell(titulo, totalRespostas, resumo) {
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
  wrapper.appendChild(card);
  return {
    wrapper,
    body: card.querySelector('.question-body'),
    controls: card.querySelector('.controls-slot'),
  };
}

function buildCardShellHalf(titulo, totalRespostas, resumo) {
  const shell = buildCardShell(titulo, totalRespostas, resumo);
  shell.wrapper.className = 'col-12 col-lg-6';
  return shell;
}

// ─── Aggregação yearsMap (compartilhada entre matrix block e bi matrizes) ───

function aggregateYearsMap(matriz, linhaCodigo, medida) {
  if (linhaCodigo !== '__ALL__') {
    return matriz?.valores?.[linhaCodigo]?.[medida] || {};
  }
  const result = {};
  (matriz.linhas || []).forEach(({ codigo }) => {
    const byYear = matriz?.valores?.[codigo]?.[medida] || {};
    Object.entries(byYear).forEach(([ano, byMunicipio]) => {
      if (!result[ano]) result[ano] = {};
      Object.entries(byMunicipio || {}).forEach(([municipio, valor]) => {
        result[ano][municipio] = Number(result[ano][municipio] || 0) + Number(valor || 0);
      });
    });
  });
  return result;
}

function extractMunicipios(yearsMap, anos) {
  const set = new Set();
  anos.forEach((ano) => {
    Object.keys(yearsMap[ano] || {}).forEach((m) => set.add(m));
  });
  return Array.from(set).sort((a, b) => a.localeCompare(b, 'pt-BR', { sensitivity: 'base' }));
}

function buildYearDatasets(yearsMap, anos, municipios) {
  return anos.map((ano, idx) => ({
    label: ano,
    data: municipios.map((m) => Number((yearsMap[ano] || {})[m] || 0)),
    backgroundColor: bg(idx),
    borderColor: bg(idx),
    tension: 0.2,
  }));
}

// ─── Text Answers Modal ─────────────────────────────────────

function createTextModal() {
  const el = document.getElementById('textAnswersModal');
  if (!el) return { open() {} };

  const titleEl = el.querySelector('.js-text-modal-title');
  const listEl = el.querySelector('.js-text-modal-list');
  const countEl = el.querySelector('.js-text-modal-count');
  let instance = null;

  return {
    open(pergunta, respostas) {
      const lista = Array.isArray(respostas) ? respostas : [];
      const titulo = cleanText(pergunta?.texto || 'Respostas');

      if (!window.bootstrap?.Modal) {
        alert(`${titulo}\n\n${lista.map((r) => `- ${cleanText(r)}`).join('\n') || 'Sem respostas abertas.'}`);
        return;
      }

      if (!instance) instance = new window.bootstrap.Modal(el);
      if (titleEl) titleEl.textContent = titulo;
      if (countEl) countEl.textContent = `${lista.length} resposta(s)`;

      if (listEl) {
        listEl.innerHTML = '';
        if (!lista.length) {
          listEl.innerHTML = '<div class="text-muted">Sem respostas abertas.</div>';
        } else {
          lista.forEach((resp) => {
            const item = document.createElement('div');
            item.className = 'p-2 rounded border bg-light';
            item.textContent = cleanText(resp);
            listEl.appendChild(item);
          });
        }
      }

      instance.show();
    },
  };
}

// ─── Chart type resolution ──────────────────────────────────

function resolveChartType(pergunta, labels, userPref) {
  if (userPref && userPref !== 'auto') return userPref;
  if (pergunta.tipo === 'boolean') return 'doughnut';
  if (pergunta.tipo === 'numero') return 'line';
  if (pergunta.tipo === 'escala') return 'bar';
  return labels.length > 3 ? 'polarArea' : 'bar';
}

// ─── Sub-renderers for renderSimpleQuestionCard ─────────────

function renderTextQuestion(body, pergunta, listaFonte, modal) {
  const PREVIEW = 5;
  const list = document.createElement('div');
  list.className = 'vstack gap-2';

  const itens = listaFonte.slice(0, PREVIEW);
  if (!itens.length) {
    list.innerHTML = '<div class="text-muted">Sem respostas abertas.</div>';
  } else {
    itens.forEach((resp) => {
      const item = document.createElement('div');
      item.className = 'p-2 rounded border bg-light';
      item.textContent = cleanText(resp);
      list.appendChild(item);
    });
  }

  if (listaFonte.length > PREVIEW) {
    const hint = document.createElement('div');
    hint.className = 'text-muted small';
    hint.textContent = `Mostrando ${PREVIEW} de ${listaFonte.length} resposta(s)`;
    list.appendChild(hint);
  }
  body.appendChild(list);

  if (listaFonte.length > PREVIEW) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-primary btn-sm align-self-start mt-1';
    btn.textContent = `Ver todas as respostas (${listaFonte.length})`;
    btn.addEventListener('click', () => modal.open(pergunta, listaFonte));
    body.appendChild(btn);
  }
}

function renderGenericChart(body, controls, pergunta, chartInstances, chartPreferences, rerender) {
  const canvas = createCanvas(body);
  destroyChart(chartInstances, pergunta.id);

  const labels = (pergunta.labels || []).map(cleanText);
  const colors = labels.map((_, i) => bg(i));
  const typeOptions = [
    ['auto', 'Auto'], ['bar', 'Barras (vertical)'], ['bar-horizontal', 'Barras (horizontal)'],
    ['doughnut', 'Pizza'], ['polarArea', 'Polar'], ['line', 'Linha'],
  ];

  const userPref = chartPreferences.get(pergunta.id);
  const chartType = resolveChartType(pergunta, labels, userPref);

  if (controls) {
    controls.appendChild(makeSelect(
      'form-select form-select-sm', '150px', typeOptions, userPref || 'auto',
      (e) => {
        if (e.target.value === 'auto') chartPreferences.delete(pergunta.id);
        else chartPreferences.set(pergunta.id, e.target.value);
        rerender();
      },
    ));
  }

  const base = chartType === 'bar-horizontal' ? 'bar' : chartType;
  const data = {
    labels,
    datasets: [{
      label: 'Respostas',
      data: pergunta.values,
      backgroundColor: base === 'line' ? 'rgba(66,25,68,0.15)' : colors,
      borderColor: PALETTE[0],
      tension: 0.2,
      fill: base === 'line',
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
  if (base === 'doughnut' || base === 'polarArea') delete options.scales;
  const autoH = !userPref && base === 'bar' && labels.length > 4;
  if (base === 'bar' && (chartType === 'bar-horizontal' || autoH)) options.indexAxis = 'y';

  chartInstances.set(pergunta.id, new Chart(canvas, { type: base, data, options }));
}

function renderMunicipioLevel(body, controls, pergunta, chartInstances, chartPreferences, rerender) {
  const chartKey = `${pergunta.id}::level`;
  const prefKey = `${pergunta.id}::level_type`;
  const labels = (pergunta.municipio_labels || []).map(cleanText);
  const values = (pergunta.municipio_levels || []).map((v) => Number(v || 0));

  if (!labels.length) {
    body.innerHTML = '<div class="text-muted">Sem dados para esta questao.</div>';
    return;
  }

  if (controls) {
    controls.appendChild(makeSelect(
      'form-select form-select-sm', '170px',
      [['bar-horizontal', 'Barras (horizontal)'], ['bar', 'Barras (vertical)'], ['line', 'Linha']],
      chartPreferences.get(prefKey) || 'bar-horizontal',
      (e) => { chartPreferences.set(prefKey, e.target.value); rerender(); },
    ));
  }

  const canvas = createCanvas(body);
  destroyChart(chartInstances, chartKey);

  const selected = chartPreferences.get(prefKey) || 'bar-horizontal';
  const base = selected === 'bar-horizontal' ? 'bar' : selected;
  chartInstances.set(chartKey, new Chart(canvas, {
    type: base,
    data: {
      labels,
      datasets: [{ label: 'Nivel', data: values, backgroundColor: PALETTE[1], borderColor: PALETTE[1], tension: 0.25, fill: base === 'line' }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, title: { display: true, text: 'Nivel de acompanhamento por municipio' } },
      scales: {
        x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
        y: { ticks: { color: '#64748b', precision: 0 } },
      },
      indexAxis: selected === 'bar-horizontal' ? 'y' : 'x',
    },
  }));
}

function renderMunicipioMultiselect(body, controls, pergunta, chartInstances, chartPreferences, rerender) {
  const totalsKey = `${pergunta.id}::totais`;
  const munKey = `${pergunta.id}::municipios`;
  const prefKey = `${pergunta.id}::multiselect_mode`;
  const stackMode = chartPreferences.get(prefKey) || 'stacked';

  if (controls) {
    controls.appendChild(makeSelect(
      'form-select form-select-sm', '170px',
      [['stacked', 'Composicao empilhada'], ['grouped', 'Composicao agrupada']],
      stackMode,
      (e) => { chartPreferences.set(prefKey, e.target.value); rerender(); },
    ));
  }

  const totaisLabels = (pergunta.totais_labels || []).map(cleanText);
  const totaisValues = (pergunta.totais_values || []).map((v) => Number(v || 0));
  const municipioLabels = (pergunta.municipio_labels || []).map(cleanText);
  const rawSeries = Array.isArray(pergunta.municipio_series) ? pergunta.municipio_series : [];

  const sequence = totaisLabels.length ? totaisLabels : rawSeries.map((s) => cleanText(s.label || s.code || ''));
  const seriesMap = new Map(rawSeries.map((s) => [cleanText(s.label || s.code || ''), s]));
  const orderedSeries = sequence.map((l) => seriesMap.get(l)).filter(Boolean);
  rawSeries.forEach((s) => { if (!orderedSeries.includes(s)) orderedSeries.push(s); });

  if (!municipioLabels.length || !orderedSeries.length) {
    body.innerHTML = '<div class="text-muted">Sem dados para esta questao.</div>';
    return;
  }

  const addSection = (text) => {
    const el = document.createElement('div');
    el.className = 'small fw-semibold text-muted mb-2';
    el.textContent = text;
    body.appendChild(el);
  };

  addSection('Numero de municipios por opcao');
  const totalsCanvas = createCanvas(body, 110);

  body.appendChild(Object.assign(document.createElement('div'), { className: 'my-3' }));

  addSection('Composicao por municipio');
  const munCanvas = createCanvas(body, 130);

  destroyChart(chartInstances, totalsKey);
  destroyChart(chartInstances, munKey);

  chartInstances.set(totalsKey, new Chart(totalsCanvas, {
    type: 'bar',
    data: {
      labels: totaisLabels.length ? totaisLabels : orderedSeries.map((s) => cleanText(s.label || s.code || '')),
      datasets: [{
        label: 'Municipios',
        data: totaisValues.length ? totaisValues : orderedSeries.map((s) => (Array.isArray(s.data) ? s.data.reduce((a, c) => a + Number(c || 0), 0) : 0)),
        backgroundColor: (totaisLabels.length ? totaisLabels : orderedSeries).map((_, i) => bg(i)),
      }],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
        y: { ticks: { color: '#64748b', precision: 0 } },
      },
    },
  }));

  const datasets = orderedSeries.map((s, i) => ({
    label: cleanText(s.label || s.code || `Serie ${i + 1}`),
    data: Array.isArray(s.data) ? s.data.map((v) => Number(v || 0)) : [],
    backgroundColor: bg(i),
    borderColor: bg(i),
  }));

  chartInstances.set(munKey, new Chart(munCanvas, {
    type: 'bar',
    data: { labels: municipioLabels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: {
        x: { stacked: stackMode === 'stacked', ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
        y: { stacked: stackMode === 'stacked', ticks: { color: '#64748b', precision: 0 } },
      },
    },
  }));
}

function renderMunicipioSeries(body, controls, pergunta, chartInstances, chartPreferences, rerender) {
  const chartKey = `${pergunta.id}::municipio-series`;
  const prefKey = `${pergunta.id}::municipio-series-mode`;
  const mode = chartPreferences.get(prefKey) || (pergunta.chart_mode === 'grouped' ? 'grouped' : 'stacked');

  if (controls) {
    controls.appendChild(makeSelect(
      'form-select form-select-sm', '170px',
      [['stacked', 'Composicao empilhada'], ['grouped', 'Composicao agrupada']],
      mode,
      (e) => { chartPreferences.set(prefKey, e.target.value); rerender(); },
    ));
  }

  const canvas = createCanvas(body);
  destroyChart(chartInstances, chartKey);

  const labels = (pergunta.municipio_labels || []).map(cleanText);
  const datasets = (pergunta.municipio_series || []).map((s, i) => ({
    label: cleanText(s.label || s.code || `Serie ${i + 1}`),
    data: Array.isArray(s.data) ? s.data.map((n) => Number(n || 0)) : [],
    backgroundColor: bg(i),
    borderColor: bg(i),
  }));

  chartInstances.set(chartKey, new Chart(canvas, {
    type: 'bar',
    data: { labels, datasets },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: {
        x: { stacked: mode === 'stacked', ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
        y: { stacked: mode === 'stacked', ticks: { color: '#64748b', precision: 0 } },
      },
    },
  }));
}

// ─── Renderers (orquestração) ───────────────────────────────

function renderSimpleQuestionCard(pergunta, titleOverride, ctx) {
  const titulo = cleanText(titleOverride || pergunta.texto);
  const resumo = cleanText(pergunta.resumo || '');
  const { wrapper, body, controls } = buildCardShell(titulo, pergunta.total || 0, resumo);
  const rerender = () => ctx.renderBlocks(ctx.cachedBlocks);

  const respostas = Array.isArray(pergunta.respostas) ? pergunta.respostas : [];
  const exemplos = Array.isArray(pergunta.exemplos) ? pergunta.exemplos : [];

  if (pergunta.tipo === 'municipio_level' && Array.isArray(pergunta.municipio_labels)) {
    renderMunicipioLevel(body, controls, pergunta, ctx.charts, ctx.prefs, rerender);
  } else if (pergunta.tipo === 'municipio_multiselect' && Array.isArray(pergunta.municipio_series)) {
    renderMunicipioMultiselect(body, controls, pergunta, ctx.charts, ctx.prefs, rerender);
  } else if (pergunta.tipo === 'municipio_series' && Array.isArray(pergunta.municipio_series)) {
    renderMunicipioSeries(body, controls, pergunta, ctx.charts, ctx.prefs, rerender);
  } else if (pergunta.tipo === 'texto') {
    renderTextQuestion(body, pergunta, respostas.length ? respostas : exemplos, ctx.modal);
  } else {
    renderGenericChart(body, controls, pergunta, ctx.charts, ctx.prefs, rerender);
  }

  ctx.container.appendChild(wrapper);
}

function renderMatrixBlockCard(block, ctx) {
  const matriz = block.matrix;
  const blockId = `matrix-${block.id}`;
  if (!ctx.matrixState.has(blockId)) {
    ctx.matrixState.set(blockId, { linhaCodigo: '__ALL__', medida: (matriz.medidas || [])[0] || null, chartType: 'bar' });
  }
  const state = ctx.matrixState.get(blockId);

  const titulo = cleanText(block.title || matriz.texto || block.id);
  const { wrapper, body, controls } = buildCardShell(titulo, 0, '');
  body.closest('.card-body').querySelector('.text-muted').textContent = 'Questao matriz';

  const linhaSelect = makeSelect('form-select form-select-sm', '220px',
    [['__ALL__', 'Todas as subquestoes'], ...(matriz.linhas || []).map((l) => [l.codigo, cleanText(l.label || l.codigo)])],
    state.linhaCodigo || '__ALL__', draw);

  const medidaSelect = makeSelect('form-select form-select-sm', '170px',
    (matriz.medidas || []).map((m) => [m, cleanText(m)]),
    state.medida || ((matriz.medidas || [])[0] || ''), draw);

  const tipoSelect = makeSelect('form-select form-select-sm', '150px',
    [['bar', 'Barras'], ['line', 'Linha']], state.chartType || 'bar', draw);

  controls.appendChild(linhaSelect);
  controls.appendChild(medidaSelect);
  controls.appendChild(tipoSelect);

  const meta = document.createElement('div');
  meta.className = 'text-muted small mb-2';
  body.appendChild(meta);
  const canvas = createCanvas(body);

  function draw() {
    state.linhaCodigo = linhaSelect.value;
    state.medida = medidaSelect.value;
    state.chartType = tipoSelect.value;

    const yearsMap = aggregateYearsMap(matriz, state.linhaCodigo, state.medida);
    const anos = (matriz.anos || []).filter((a) => Object.prototype.hasOwnProperty.call(yearsMap, a));
    const municipios = extractMunicipios(yearsMap, anos);

    destroyChart(ctx.charts, blockId);

    if (!anos.length || !municipios.length) {
      meta.textContent = 'Sem dados para os filtros selecionados.';
      return;
    }

    const linhaLabel = state.linhaCodigo === '__ALL__'
      ? 'Todas as subquestoes'
      : cleanText((matriz.linhas || []).find((i) => i.codigo === state.linhaCodigo)?.label || state.linhaCodigo);

    ctx.charts.set(blockId, new Chart(canvas, {
      type: state.chartType,
      data: { labels: municipios.map(cleanText), datasets: buildYearDatasets(yearsMap, anos, municipios) },
      options: {
        responsive: true,
        plugins: { legend: { display: true }, title: { display: true, text: `${linhaLabel} - ${cleanText(state.medida)}` } },
        scales: {
          x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
          y: { ticks: { color: '#64748b', precision: 0 } },
        },
      },
    }));
    meta.textContent = `Campo de municipio: ${matriz.municipio_field || 'nao identificado'} | Municipios exibidos: ${municipios.length}`;
  }

  draw();
  ctx.container.appendChild(wrapper);
}

function renderLegacyCharts(perguntas, ctx) {
  const rerender = () => renderLegacyCharts(ctx.cachedPerguntas, ctx);

  perguntas.forEach((pergunta) => {
    const titulo = cleanText(pergunta.texto);
    const resumo = cleanText(pergunta.resumo || '');
    const { wrapper, body, controls } = buildCardShellHalf(titulo, pergunta.total || 0, resumo);

    const respostas = Array.isArray(pergunta.respostas) ? pergunta.respostas : [];
    const exemplos = Array.isArray(pergunta.exemplos) ? pergunta.exemplos : [];

    if (pergunta.tipo === 'texto') {
      renderTextQuestion(body, pergunta, respostas.length ? respostas : exemplos, ctx.modal);
    } else {
      renderGenericChart(body, controls, pergunta, ctx.charts, ctx.prefs, rerender);
    }

    ctx.container.appendChild(wrapper);
  });
}

// ─── BI Matrizes ────────────────────────────────────────────

function renderBiMatrizes(matrizes, ctx) {
  ctx.cachedBiMatrizes = Array.isArray(matrizes) ? matrizes : [];
  const section = document.getElementById('bi-matriz-section');
  const container = document.getElementById('bi-matriz-container');
  if (!section || !container) return;

  if (!ctx.cachedBiMatrizes.length) {
    section.style.display = 'none';
    container.innerHTML = '';
    destroyChart(ctx.charts, '__bi_matriz__');
    return;
  }

  section.style.display = '';
  container.innerHTML = `
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

  const matrizSelect = container.querySelector('#bi-matriz-select');
  const linhaSelect = container.querySelector('#bi-matriz-linha');
  const medidaSelect = container.querySelector('#bi-matriz-medida');
  const canvas = container.querySelector('#bi-matriz-canvas');
  const metaEl = container.querySelector('#bi-matriz-meta');

  ctx.cachedBiMatrizes.forEach((m) => {
    const opt = document.createElement('option');
    opt.value = m.codigo;
    opt.textContent = `${m.codigo} - ${cleanText(m.texto || m.codigo)}`;
    matrizSelect.appendChild(opt);
  });

  const st = ctx.biState;
  st.matrizCodigo = st.matrizCodigo || ctx.cachedBiMatrizes[0].codigo;
  if (!ctx.cachedBiMatrizes.some((m) => m.codigo === st.matrizCodigo)) {
    st.matrizCodigo = ctx.cachedBiMatrizes[0].codigo;
  }
  matrizSelect.value = st.matrizCodigo;

  const matrizAtual = () => ctx.cachedBiMatrizes.find((m) => m.codigo === matrizSelect.value) || null;

  function popularFiltros() {
    const matriz = matrizAtual();
    if (!matriz) return;

    linhaSelect.innerHTML = '<option value="__ALL__">Todos</option>';
    (matriz.linhas || []).forEach((l) => {
      const opt = document.createElement('option');
      opt.value = l.codigo;
      opt.textContent = cleanText(l.label || l.codigo);
      linhaSelect.appendChild(opt);
    });

    medidaSelect.innerHTML = '';
    (matriz.medidas || []).forEach((m) => {
      const opt = document.createElement('option');
      opt.value = m;
      opt.textContent = cleanText(m);
      medidaSelect.appendChild(opt);
    });

    const linhasCods = (matriz.linhas || []).map((l) => l.codigo);
    const medidas = matriz.medidas || [];
    if (st.linhaCodigo !== '__ALL__' && !linhasCods.includes(st.linhaCodigo)) st.linhaCodigo = linhasCods[0] || '__ALL__';
    if (!medidas.includes(st.medida)) st.medida = medidas[0] || null;
    if (!st.linhaCodigo) st.linhaCodigo = '__ALL__';

    linhaSelect.value = st.linhaCodigo;
    if (st.medida) medidaSelect.value = st.medida;
  }

  function renderGrafico() {
    const matriz = matrizAtual();
    if (!matriz || !canvas) return;

    st.matrizCodigo = matriz.codigo;
    st.linhaCodigo = linhaSelect.value;
    st.medida = medidaSelect.value;

    const yearsMap = aggregateYearsMap(matriz, st.linhaCodigo, st.medida);
    const anos = (matriz.anos || []).filter((a) => Object.prototype.hasOwnProperty.call(yearsMap, a));

    destroyChart(ctx.charts, '__bi_matriz__');

    if (!anos.length) {
      metaEl.textContent = 'Sem dados para os filtros selecionados.';
      return;
    }

    const municipios = extractMunicipios(yearsMap, anos);
    const linhaLabel = st.linhaCodigo === '__ALL__'
      ? 'Todas as subquestoes'
      : cleanText((matriz.linhas || []).find((i) => i.codigo === st.linhaCodigo)?.label || st.linhaCodigo);

    ctx.charts.set('__bi_matriz__', new Chart(canvas, {
      type: 'bar',
      data: { labels: municipios.map(cleanText), datasets: buildYearDatasets(yearsMap, anos, municipios) },
      options: {
        responsive: true,
        plugins: { legend: { display: true }, title: { display: true, text: `${linhaLabel} - ${cleanText(st.medida)}` } },
        scales: {
          x: { ticks: { color: '#64748b', maxRotation: 50, minRotation: 25 } },
          y: { ticks: { color: '#64748b', precision: 0 } },
        },
      },
    }));
    metaEl.textContent = `Campo de municipio: ${matriz.municipio_field || 'nao identificado'} | Municipios exibidos: ${municipios.length}`;
  }

  matrizSelect.addEventListener('change', () => { popularFiltros(); renderGrafico(); });
  linhaSelect.addEventListener('change', renderGrafico);
  medidaSelect.addEventListener('change', renderGrafico);
  popularFiltros();
  renderGrafico();
}

// ─── Bootstrap (ponto de entrada) ───────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('avaliacoes-dashboard');
  if (!root) return;

  const endpoint = root.dataset.endpoint;
  const cardsQuestoes = document.getElementById('cards-questoes');
  const modal = createTextModal();

  const ctx = {
    container: cardsQuestoes,
    charts: new Map(),
    prefs: new Map(),
    matrixState: new Map(),
    modal,
    cachedBlocks: [],
    cachedPerguntas: [],
    cachedBiMatrizes: [],
    biState: { matrizCodigo: null, linhaCodigo: null, medida: null },

    renderBlocks(blocks) {
      const lista = Array.isArray(blocks) ? blocks : [];
      ctx.cachedBlocks = lista;

      const biSection = document.getElementById('bi-matriz-section');
      if (biSection) biSection.style.display = 'none';

      cardsQuestoes.innerHTML = '';
      if (!lista.length) {
        cardsQuestoes.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body text-muted text-center">Sem respostas para os filtros aplicados.</div></div></div>';
        return;
      }
      lista.forEach((block) => {
        if (block?.kind === 'matrix' && block?.matrix) renderMatrixBlockCard(block, ctx);
        else if (block?.kind === 'simple' && block?.question) renderSimpleQuestionCard(block.question, block.title || block.question?.texto, ctx);
      });
    },
  };

  const filters = {
    template: document.getElementById('f-template'),
    evento: document.getElementById('f-evento'),
    atividade: document.getElementById('f-atividade'),
    de: document.getElementById('f-de'),
    ate: document.getElementById('f-ate'),
  };
  const totalsEls = {
    submissoes: document.querySelector('[data-total="submissoes"]'),
    questoes: document.querySelector('[data-total="questoes"]'),
    eventos: document.querySelector('[data-total="eventos"]'),
    ultima: document.querySelector('[data-total="ultima"]'),
  };

  function buildParams() {
    const params = new URLSearchParams();
    if (filters.template?.value) params.set('template_id', filters.template.value);
    if (filters.evento?.value) params.set('evento_id', filters.evento.value);
    if (filters.atividade?.value) params.set('atividade_id', filters.atividade.value);
    if (filters.de?.value) params.set('de', filters.de.value);
    if (filters.ate?.value) params.set('ate', filters.ate.value);
    return params.toString();
  }

  function setLoading(on) {
    if (on) {
      cardsQuestoes.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body text-center text-muted">Carregando graficos...</div></div></div>';
    }
  }

  function renderTotals(totais) {
    totalsEls.submissoes.textContent = fmt.format(totais.submissoes || 0);
    totalsEls.questoes.textContent = fmt.format(totais.questoes || 0);
    totalsEls.eventos.textContent = fmt.format(totais.eventos || 0);
    totalsEls.ultima.textContent = totais.ultima || '-';
  }

  async function loadData() {
    setLoading(true);
    try {
      const url = `${endpoint}?${buildParams()}`;
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload?.erro || 'Erro ao carregar dados.');

      renderTotals(payload.totais || {});

      if (Array.isArray(payload.question_blocks) && payload.question_blocks.length > 0) {
        ctx.renderBlocks(payload.question_blocks);
      } else {
        renderBiMatrizes(payload.bi_matrizes || [], ctx);
        cardsQuestoes.innerHTML = '';
        ctx.cachedPerguntas = payload.perguntas || [];
        if (!ctx.cachedPerguntas.length) {
          cardsQuestoes.innerHTML = '<div class="col-12"><div class="card border-0 shadow-sm"><div class="card-body text-muted text-center">Sem respostas para os filtros aplicados.</div></div></div>';
        } else {
          renderLegacyCharts(ctx.cachedPerguntas, ctx);
        }
      }
    } catch (error) {
      const msg = error?.message || 'Erro ao carregar dados.';
      renderBiMatrizes([], ctx);
      cardsQuestoes.innerHTML = `<div class="card border-0 shadow-sm"><div class="card-body text-danger">${msg}</div></div>`;
    }
  }

  document.querySelectorAll('.js-filter').forEach((el) => el.addEventListener('change', loadData));
  document.getElementById('btn-recarregar')?.addEventListener('click', loadData);
  loadData();
});
