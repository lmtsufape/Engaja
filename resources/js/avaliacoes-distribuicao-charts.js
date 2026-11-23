/**
 * Gráficos de distribuição por questão (Chart.js) — dashboard (fetch) ou momento (dados inline).
 * Exige window.Chart (Chart.js 4.x) carregado antes deste bundle.
 */

const palette = [
    "#421944",
    "#008BBC",
    "#FDB913",
    "#E62270",
    "#2EB57D",
    "#601F69",
    "#6C345E",
    "#9602C7",
    "#A95DB1",
    "#D9A8E2",
    "#ECDEEC",
];

function cleanText(value) {
    if (!value) return "";
    const text = String(value).replace(/<[^>]+>/g, " ");
    return text.replace(/\s+/g, " ").trim();
}

function wrapText(text, maxLength = 40) {
    if (!text || text.length <= maxLength) return text;
    const words = text.split(" ");
    const lines = [];
    let current = "";
    words.forEach((word) => {
        if ((current + word).length > maxLength) {
            if (current.trim() !== "") lines.push(current.trim());
            current = word + " ";
        } else {
            current += word + " ";
        }
    });
    if (current.trim() !== "") lines.push(current.trim());
    return lines;
}

function normalizeValues(pergunta) {
    const raw = pergunta.values;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw.map((v) => Number(v) || 0);
    if (typeof raw === "object" && raw !== null)
        return Object.values(raw).map((v) => Number(v) || 0);
    return [];
}

function bootFetch(root) {
    const endpoint = root.dataset.endpoint;
    if (!endpoint) return;

    const filters = {
        template: document.getElementById("f-template"),
        evento: document.getElementById("f-evento"),
        atividade: document.getElementById("f-atividade"),
        de: document.getElementById("f-de"),
        ate: document.getElementById("f-ate"),
    };
    const totalsEls = {
        submissoes: document.querySelector('[data-total="submissoes"]'),
        questoes: document.querySelector('[data-total="questoes"]'),
        eventos: document.querySelector('[data-total="eventos"]'),
        ultima: document.querySelector('[data-total="ultima"]'),
    };
    const cardsQuestoes = document.getElementById("cards-questoes");
    const refreshBtn = document.getElementById("btn-recarregar");
    const textModalEl = document.getElementById("textAnswersModal");
    const textModalTitle = textModalEl?.querySelector(".js-text-modal-title");
    const textModalList = textModalEl?.querySelector(".js-text-modal-list");
    const textModalCount = textModalEl?.querySelector(".js-text-modal-count");
    let textModalInstance = null;

    const chartInstances = new Map();
    const chartPreferences = new Map();
    let cachedPerguntas = [];

    const defaultHorizontalMode = false;
    const groupSections = false;

    function buildParams() {
        const params = new URLSearchParams();
        if (filters.template?.value)
            params.set("template_id", filters.template.value);
        if (filters.evento?.value) params.set("evento_id", filters.evento.value);
        if (filters.atividade?.value)
            params.set("atividade_id", filters.atividade.value);
        if (filters.de?.value) params.set("de", filters.de.value);
        if (filters.ate?.value) params.set("ate", filters.ate.value);
        return params.toString();
    }

    function setLoading(state) {
        if (!cardsQuestoes) return;
        if (state) {
            cardsQuestoes.innerHTML = `
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted">Carregando graficos...</div>
          </div>
        </div>`;
        }
    }

    function openTextModal(pergunta, respostas) {
        const lista = Array.isArray(respostas) ? respostas : [];
        const titulo = cleanText(pergunta?.texto || "Respostas");
        const total = lista.length;

        if (!textModalEl || !window.bootstrap?.Modal) {
            const texto = lista.length
                ? lista.map((resp) => `- ${cleanText(resp)}`).join("\n")
                : "Sem respostas abertas.";
            alert(`${titulo}\n\n${texto}`);
            return;
        }

        if (!textModalInstance) {
            textModalInstance = new window.bootstrap.Modal(textModalEl);
        }

        if (textModalTitle) textModalTitle.textContent = titulo;
        if (textModalCount)
            textModalCount.textContent = `${total} resposta(s)`;
        if (textModalList) {
            textModalList.innerHTML = "";
            if (total === 0) {
                textModalList.innerHTML =
                    '<div class="text-muted">Sem respostas abertas.</div>';
            } else {
                lista.forEach((resp) => {
                    const item = document.createElement("div");
                    item.className = "p-2 rounded border bg-light";
                    item.textContent = cleanText(resp);
                    textModalList.appendChild(item);
                });
            }
        }

        textModalInstance.show();
    }

    function renderTotals(totais) {
        if (totalsEls.submissoes)
            totalsEls.submissoes.textContent = new Intl.NumberFormat(
                "pt-BR",
            ).format(totais.submissoes || 0);
        if (totalsEls.questoes)
            totalsEls.questoes.textContent = new Intl.NumberFormat(
                "pt-BR",
            ).format(totais.questoes || 0);
        if (totalsEls.eventos)
            totalsEls.eventos.textContent = new Intl.NumberFormat(
                "pt-BR",
            ).format(totais.eventos || 0);
        if (totalsEls.ultima)
            totalsEls.ultima.textContent = totais.ultima || "-";
    }

    function resolveChartType(pergunta, labels) {
        const userPref = chartPreferences.get(pergunta.id);
        if (userPref && userPref !== "auto") return userPref;
        if (pergunta.tipo === "boolean") return "doughnut";
        if (pergunta.tipo === "numero") return "line";
        if (pergunta.tipo === "escala") return "bar";
        if (pergunta.tipo === "unica") return "bar";
        return labels.length > 3 ? "polarArea" : "bar";
    }

    function renderCharts(perguntas) {
        cachedPerguntas = perguntas;
        if (!cardsQuestoes) return;
        if (!perguntas || perguntas.length === 0) {
            chartInstances.forEach((ch) => {
                try {
                    ch.destroy();
                } catch {
                    /* ignore */
                }
            });
            chartInstances.clear();
            cardsQuestoes.innerHTML = `
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-muted text-center">Sem respostas para os filtros aplicados.</div>
          </div>
        </div>`;
            return;
        }

        renderChartsInner({
            perguntas,
            cardsQuestoes,
            chartInstances,
            chartPreferences,
            cachedPerguntasRef: () => cachedPerguntas,
            setCachedPerguntas: (p) => {
                cachedPerguntas = p;
            },
            openTextModal,
            resolveChartType,
            defaultHorizontalMode,
            groupSections,
        });
    }

    async function loadData() {
        setLoading(true);
        try {
            const baseParams = buildParams();
            const url = `${endpoint}?${baseParams}&page=1`;
            const response = await fetch(url, { headers: { Accept: "application/json" } });
            const payload = await response.json();

            renderTotals(payload.totais || {});
            renderCharts(payload.perguntas || []);

            const meta = payload.meta || {};
            if (meta.last_page && meta.last_page > 1) {
                loadRemainingPages(baseParams, meta.last_page);
            }
        } catch {
            if (cardsQuestoes)
                cardsQuestoes.innerHTML =
                    '<div class="card border-0 shadow-sm"><div class="card-body text-danger">Erro ao carregar dados.</div></div>';
        }
    }

    async function loadRemainingPages(baseParams, lastPage) {
        for (let page = 2; page <= lastPage; page++) {
            try {
                const url = `${endpoint}?${baseParams}&page=${page}`;
                const response = await fetch(url, { headers: { Accept: "application/json" } });
                const payload = await response.json();
                const novasPerguntas = payload.perguntas || [];
                if (novasPerguntas.length === 0) break;

                cachedPerguntas = [...cachedPerguntas, ...novasPerguntas];
                appendCharts(novasPerguntas);
            } catch {
                break;
            }
        }
    }

    function appendCharts(perguntas) {
        if (!cardsQuestoes || !perguntas.length) return;

        perguntas.forEach((pergunta) => {
            const numQuestao = cardsQuestoes.querySelectorAll(".col-12.col-md-6").length + 1;
            const totalRespostas = pergunta.total || 0;
            const titulo = cleanText(pergunta.texto);
            const resumo = cleanText(pergunta.resumo || "");

            const wrapper = document.createElement("div");
            wrapper.className = "col-12 col-md-6";
            const card = document.createElement("div");
            card.className = "card border-0 shadow-sm h-100";
            card.innerHTML = `
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2 question-header">
            <div>
              <div class="fw-bold">${titulo}</div>
              <small class="text-muted">${totalRespostas} resposta(s)</small>
            </div>
            <div class="d-flex align-items-start gap-2 controls-slot question-controls">
              ${resumo ? `<span class="badge bg-primary-subtle text-primary">${resumo}</span>` : ""}
            </div>
          </div>
          <div class="question-body mt-2"></div>
        </div>
      `;
            const body = card.querySelector(".question-body");
            const controlsSlot = card.querySelector(".controls-slot");

            const isText = pergunta.tipo === "texto";
            const respostas = Array.isArray(pergunta.respostas) ? pergunta.respostas : [];
            const exemplos = Array.isArray(pergunta.exemplos) ? pergunta.exemplos : [];

            if (isText) {
                const listaFonte = respostas.length ? respostas : exemplos;
                const limitePreview = 5;
                const list = document.createElement("div");
                list.className = "vstack gap-2";
                const itens = listaFonte.slice(0, limitePreview);
                if (itens.length === 0) {
                    list.innerHTML = '<div class="text-muted">Sem respostas abertas.</div>';
                } else {
                    itens.forEach((resp) => {
                        const item = document.createElement("div");
                        item.className = "p-2 rounded border bg-light";
                        item.textContent = cleanText(resp);
                        list.appendChild(item);
                    });
                }
                if (listaFonte.length > limitePreview) {
                    const hint = document.createElement("div");
                    hint.className = "text-muted small";
                    hint.textContent = `Mostrando ${limitePreview} de ${listaFonte.length} resposta(s)`;
                    list.appendChild(hint);
                    const toggleBtn = document.createElement("button");
                    toggleBtn.type = "button";
                    toggleBtn.className = "btn btn-outline-primary btn-sm align-self-start mt-1";
                    toggleBtn.textContent = `Ver todas as respostas (${listaFonte.length})`;
                    toggleBtn.addEventListener("click", () => openTextModal(pergunta, listaFonte));
                    body.appendChild(list);
                    body.appendChild(toggleBtn);
                } else {
                    body.appendChild(list);
                }
            } else {
                const canvas = document.createElement("canvas");
                canvas.height = 120;
                body.appendChild(canvas);

                const labels = (pergunta.labels || []).map((label) =>
                    wrapText(cleanText(label === "Nao" ? "Não" : label))
                );
                const values = normalizeValues(pergunta);
                const bg = labels.map((_, idx) => palette[idx % palette.length]);
                const chartType = resolveChartType(pergunta, labels);

                const typeOptions = [
                    { value: "auto", label: "Auto" },
                    { value: "bar", label: "Barras (vertical)" },
                    { value: "bar-horizontal", label: "Barras (horizontal)" },
                    { value: "doughnut", label: "Pizza" },
                    { value: "polarArea", label: "Polar" },
                    { value: "line", label: "Linha" },
                ];
                if (controlsSlot) {
                    const select = document.createElement("select");
                    select.className = "form-select form-select-sm";
                    select.style.minWidth = "150px";
                    typeOptions.forEach((opt) => {
                        const option = document.createElement("option");
                        option.value = opt.value;
                        option.textContent = opt.label;
                        select.appendChild(option);
                    });
                    const userPref = chartPreferences.get(pergunta.id);
                    select.value = userPref || "auto";
                    select.addEventListener("change", (event) => {
                        const value = event.target.value;
                        if (value === "auto") {
                            chartPreferences.delete(pergunta.id);
                        } else {
                            chartPreferences.set(pergunta.id, value);
                        }
                        renderChartsInner({ ...buildRenderOpts(), perguntas: cachedPerguntas });
                    });
                    controlsSlot.appendChild(select);
                }

                const baseChartType = chartType === "bar-horizontal" ? "bar" : chartType;
                const userPref = chartPreferences.get(pergunta.id);
                const autoHorizontal = !userPref && baseChartType === "bar" && labels.length > 4 && !defaultHorizontalMode;
                const chartOptions = {
                    responsive: true,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    if (!context.length) return '';
                                    return context[0].chart.data.labels[context[0].dataIndex];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: "#64748b" } },
                        y: { ticks: { color: "#64748b", precision: 0 } },
                    },
                };
                if (baseChartType === "doughnut" || baseChartType === "polarArea") {
                    delete chartOptions.scales;
                }
                const isHorizontal = baseChartType === "bar" && (chartType === "bar-horizontal" || autoHorizontal);
                if (isHorizontal) {
                    chartOptions.indexAxis = "y";
                } else if (chartOptions.scales) {
                    chartOptions.scales.x.ticks.maxRotation = 45;
                    chartOptions.scales.x.ticks.minRotation = 0;
                    chartOptions.scales.x.ticks.callback = function(value, index) {
                        const label = this.chart.data.labels[index];
                        if (!label) return value;
                        const labelStr = Array.isArray(label) ? label.join(' ') : String(label);
                        if (labelStr.length > 20) return labelStr.substring(0, 20) + '...';
                        return labelStr;
                    };
                }
                const chart = new window.Chart(canvas, {
                    type: baseChartType,
                    data: {
                        labels,
                        datasets: [{
                            label: "Respostas",
                            data: values,
                            backgroundColor: baseChartType === "line" ? "rgba(66,25,68,0.15)" : bg,
                            borderColor: palette[0],
                            tension: 0.2,
                            fill: baseChartType === "line",
                        }],
                    },
                    options: chartOptions,
                });
                chartInstances.set(pergunta.id, chart);
            }

            wrapper.appendChild(card);
            cardsQuestoes.appendChild(wrapper);
        });
    }

    function buildRenderOpts() {
        return {
            cardsQuestoes,
            chartInstances,
            chartPreferences,
            cachedPerguntasRef: () => cachedPerguntas,
            setCachedPerguntas: (p) => { cachedPerguntas = p; },
            openTextModal,
            resolveChartType,
            defaultHorizontalMode,
            groupSections,
        };
    }

    document.querySelectorAll(".js-filter").forEach((input) => {
        input.addEventListener("change", loadData);
    });
    refreshBtn?.addEventListener("click", loadData);

    loadData();
}

function renderChartsInner(opts) {
    const {
        perguntas,
        cardsQuestoes,
        chartInstances,
        chartPreferences,
        cachedPerguntasRef,
        setCachedPerguntas,
        openTextModal,
        resolveChartType,
        defaultHorizontalMode,
        groupSections,
    } = opts;

    chartInstances.forEach((ch) => {
        try {
            ch.destroy();
        } catch {
            /* ignore */
        }
    });
    chartInstances.clear();
    if (cardsQuestoes) cardsQuestoes.innerHTML = "";

    let dimAtual = null;
    let indAtual = null;
    let chartRow = null;
    let numQuestao = 0;

    perguntas.forEach((pergunta) => {
        if (groupSections) {
            const dim = pergunta.dimensao || "Sem dimensão";
            const ind = pergunta.indicador || "Sem indicador";
            if (dim !== dimAtual) {
                dimAtual = dim;
                indAtual = null;
                chartRow = null;
                const h = document.createElement("div");
                h.className = "w-100 border-bottom pb-2 mb-3";
                h.style.borderColor = "#edd7fc";
                h.innerHTML = `<h2 class="h6 fw-bold mb-0" style="color:#421944;">Dimensão — ${cleanText(dim)}</h2>`;
                cardsQuestoes.appendChild(h);
            }
            if (ind !== indAtual) {
                indAtual = ind;
                const h = document.createElement("div");
                h.className = "w-100 mt-1 mb-2";
                h.innerHTML = `<p class="fw-semibold text-muted small mb-0">Indicador — ${cleanText(ind)}</p>`;
                cardsQuestoes.appendChild(h);
                chartRow = document.createElement("div");
                chartRow.className = "row g-3";
                cardsQuestoes.appendChild(chartRow);
            }
        }

        numQuestao += 1;
        const totalRespostas = pergunta.total || 0;
        const titulo = cleanText(pergunta.texto);
        const resumo = cleanText(pergunta.resumo || "");

        const wrapper = document.createElement("div");
        wrapper.className = "col-12 col-md-6";
        const card = document.createElement("div");
        card.className = "card border-0 shadow-sm h-100";
        const titlePrefix = groupSections ? `${numQuestao}. ` : "";
        card.innerHTML = `
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-2 question-header">
            <div>
              <div class="fw-bold">${titlePrefix}${titulo}</div>
              <small class="text-muted">${totalRespostas} resposta(s)</small>
            </div>
            <div class="d-flex align-items-start gap-2 controls-slot question-controls">
              ${resumo ? `<span class="badge bg-primary-subtle text-primary">${resumo}</span>` : ""}
            </div>
          </div>
          <div class="question-body mt-2"></div>
        </div>
      `;
        const body = card.querySelector(".question-body");
        const controlsSlot = card.querySelector(".controls-slot");

        const isText = pergunta.tipo === "texto";
        const respostas = Array.isArray(pergunta.respostas)
            ? pergunta.respostas
            : [];
        const exemplos = Array.isArray(pergunta.exemplos)
            ? pergunta.exemplos
            : [];

        if (isText) {
            const listaFonte = respostas.length ? respostas : exemplos;
            const limitePreview = 5;

            const list = document.createElement("div");
            list.className = "vstack gap-2";

            const itens = listaFonte.slice(0, limitePreview);
            if (itens.length === 0) {
                list.innerHTML =
                    '<div class="text-muted">Sem respostas abertas.</div>';
            } else {
                itens.forEach((resp) => {
                    const item = document.createElement("div");
                    item.className = "p-2 rounded border bg-light";
                    item.textContent = cleanText(resp);
                    list.appendChild(item);
                });
            }

            if (listaFonte.length > limitePreview) {
                const hint = document.createElement("div");
                hint.className = "text-muted small";
                hint.textContent = `Mostrando ${limitePreview} de ${listaFonte.length} resposta(s)`;
                list.appendChild(hint);
            }

            body.appendChild(list);

            if (listaFonte.length > limitePreview) {
                const toggleBtn = document.createElement("button");
                toggleBtn.type = "button";
                toggleBtn.className =
                    "btn btn-outline-primary btn-sm align-self-start mt-1";
                toggleBtn.textContent = `Ver todas as respostas (${listaFonte.length})`;
                toggleBtn.addEventListener("click", () =>
                    openTextModal(pergunta, listaFonte),
                );
                body.appendChild(toggleBtn);
            }
        } else {
            const canvas = document.createElement("canvas");
            canvas.height = 120;
            body.appendChild(canvas);

            const labels = (pergunta.labels || []).map((label) =>
                wrapText(cleanText(label === "Nao" ? "Não" : label))
            );
            const values = normalizeValues(pergunta);
            const bg = labels.map((_, idx) => palette[idx % palette.length]);

            const typeOptions = [
                { value: "auto", label: "Auto" },
                { value: "bar", label: "Barras (vertical)" },
                { value: "bar-horizontal", label: "Barras (horizontal)" },
                { value: "doughnut", label: "Pizza" },
                { value: "polarArea", label: "Polar" },
                { value: "line", label: "Linha" },
            ];

            const userPref = chartPreferences.get(pergunta.id);
            const chartType = resolveChartType(pergunta, labels);

            if (controlsSlot) {
                const select = document.createElement("select");
                select.className = "form-select form-select-sm";
                select.style.minWidth = "150px";
                typeOptions.forEach((opt) => {
                    const option = document.createElement("option");
                    option.value = opt.value;
                    option.textContent = opt.label;
                    select.appendChild(option);
                });
                select.value = userPref || "auto";
                select.addEventListener("change", (event) => {
                    const value = event.target.value;
                    if (value === "auto") {
                        chartPreferences.delete(pergunta.id);
                    } else {
                        chartPreferences.set(pergunta.id, value);
                    }
                    const cached = cachedPerguntasRef();
                    renderChartsInner({ ...opts, perguntas: cached });
                });
                controlsSlot.appendChild(select);
            }

            const baseChartType =
                chartType === "bar-horizontal" ? "bar" : chartType;
            const data = {
                labels,
                datasets: [
                    {
                        label: "Respostas",
                        data: values,
                        backgroundColor:
                            baseChartType === "line"
                                ? "rgba(66,25,68,0.15)"
                                : bg,
                        borderColor: palette[0],
                        tension: 0.2,
                        fill: baseChartType === "line",
                    },
                ],
            };

            const options = {
                responsive: true,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                if (!context.length) return '';
                                return context[0].chart.data.labels[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: "#64748b" } },
                    y: { ticks: { color: "#64748b", precision: 0 } },
                },
            };

            if (baseChartType === "doughnut" || baseChartType === "polarArea") {
                delete options.scales;
            }

            const autoHorizontal =
                !userPref &&
                baseChartType === "bar" &&
                labels.length > 4 &&
                !defaultHorizontalMode;
            
            const isHorizontal = baseChartType === "bar" && (chartType === "bar-horizontal" || autoHorizontal);
            if (isHorizontal) {
                options.indexAxis = "y";
            } else if (options.scales) {
                options.scales.x.ticks.maxRotation = 45;
                options.scales.x.ticks.minRotation = 0;
                options.scales.x.ticks.callback = function(value, index) {
                    const label = this.chart.data.labels[index];
                    if (!label) return value;
                    const labelStr = Array.isArray(label) ? label.join(' ') : String(label);
                    if (labelStr.length > 20) return labelStr.substring(0, 20) + '...';
                    return labelStr;
                };
            }

            const chart = new window.Chart(canvas, {
                type: baseChartType,
                data,
                options,
            });
            chartInstances.set(pergunta.id, chart);
        }

        wrapper.appendChild(card);
        if (groupSections) {
            if (chartRow) {
                chartRow.appendChild(wrapper);
            }
        } else {
            cardsQuestoes.appendChild(wrapper);
        }
    });

    if (setCachedPerguntas) setCachedPerguntas(perguntas);
}

function bootInline(root) {
    const jsonEl = root.querySelector(
        'script[type="application/json"][data-avaliacoes-perguntas], #avaliacoes-perguntas-json',
    );
    let perguntas = [];
    try {
        perguntas = jsonEl ? JSON.parse(jsonEl.textContent || "[]") : [];
    } catch {
        perguntas = [];
    }

    const cardsQuestoes = root.querySelector(
        "[data-cards-questoes], #cards-questoes-momento",
    );
    if (!cardsQuestoes || typeof window.Chart === "undefined") return;

    const chartInstances = new Map();
    const chartPreferences = new Map();
    const modalId = root.dataset.textModalId || "textAnswersModalMomento";
    const textModalEl = document.getElementById(modalId);
    const textModalTitle = textModalEl?.querySelector(".js-text-modal-title");
    const textModalList = textModalEl?.querySelector(".js-text-modal-list");
    const textModalCount = textModalEl?.querySelector(".js-text-modal-count");
    let textModalInstance = null;

    function openTextModal(pergunta, respostas) {
        const lista = Array.isArray(respostas) ? respostas : [];
        const titulo = cleanText(pergunta?.texto || "Respostas");
        const total = lista.length;

        if (!textModalEl || !window.bootstrap?.Modal) {
            const texto = lista.length
                ? lista.map((resp) => `- ${cleanText(resp)}`).join("\n")
                : "Sem respostas abertas.";
            alert(`${titulo}\n\n${texto}`);
            return;
        }

        if (!textModalInstance) {
            textModalInstance = new window.bootstrap.Modal(textModalEl);
        }

        if (textModalTitle) textModalTitle.textContent = titulo;
        if (textModalCount)
            textModalCount.textContent = `${total} resposta(s)`;
        if (textModalList) {
            textModalList.innerHTML = "";
            if (total === 0) {
                textModalList.innerHTML =
                    '<div class="text-muted">Sem respostas abertas.</div>';
            } else {
                lista.forEach((resp) => {
                    const item = document.createElement("div");
                    item.className = "p-2 rounded border bg-light";
                    item.textContent = cleanText(resp);
                    textModalList.appendChild(item);
                });
            }
        }

        textModalInstance.show();
    }

    const defaultHorizontalMode = true;

    function resolveChartTypeMomento(pergunta, labels) {
        const userPref = chartPreferences.get(pergunta.id);
        if (userPref && userPref !== "auto") return userPref;
        if (pergunta.tipo === "texto") return null;
        return "bar-horizontal";
    }

    if (!perguntas.length) {
        cardsQuestoes.innerHTML = `
      <div class="w-100">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-muted text-center">Nenhuma resposta agregada para este momento.</div>
        </div>
      </div>`;
        return;
    }

    let cachedPerguntas = perguntas;

    renderChartsInner({
        perguntas,
        cardsQuestoes,
        chartInstances,
        chartPreferences,
        cachedPerguntasRef: () => cachedPerguntas,
        setCachedPerguntas: (p) => {
            cachedPerguntas = p;
        },
        openTextModal,
        resolveChartType: resolveChartTypeMomento,
        defaultHorizontalMode,
        groupSections: true,
    });
}

function boot() {
    const fetchRoot = document.getElementById("avaliacoes-dashboard");
    if (fetchRoot?.dataset?.endpoint) {
        bootFetch(fetchRoot);
    }
    const inlineRoot = document.getElementById("avaliacoes-momento-root");
    if (inlineRoot) {
        bootInline(inlineRoot);
    }
    document.querySelectorAll("[data-avaliacoes-inline-root]").forEach((root) => {
        bootInline(root);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot, { once: true });
} else {
    boot();
}
