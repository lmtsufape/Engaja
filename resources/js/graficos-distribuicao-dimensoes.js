import ApexCharts from "apexcharts";

const CHART_SELECTOR = '[data-chart="distribuicao-dimensao"]';
const chartsPorElemento = new Map();
let hooksLivewireRegistrados = false;

const toNumber = (valor) => {
    const numero = Number(valor);
    return Number.isFinite(numero) ? numero : 0;
};

const obterPaletaEngaja = () => {
    const css = getComputedStyle(document.documentElement);
    const fallback = [
        "#421944",
        "#008BBC",
        "#FDB913",
        "#E62270",
        "#2EB57D",
    ];

    const vars = [
        "--engaja-purple",
        "--engaja-blue",
        "--engaja-yellow",
        "--engaja-pink",
        "--engaja-green",
    ].map((nome, idx) => css.getPropertyValue(nome).trim() || fallback[idx]);

    return vars;
};

const formatadorNumero = new Intl.NumberFormat("pt-BR", {
    maximumFractionDigits: 2,
});

const isTruthyData = (valor) =>
    String(valor).toLowerCase() === "1" ||
    String(valor).toLowerCase() === "true";

const parseDados = (dadosBrutos) => {
    if (!dadosBrutos) {
        return [];
    }

    try {
        const dados = JSON.parse(dadosBrutos);
        if (!Array.isArray(dados)) {
            return [];
        }

        return dados
            .map((item) => ({
                label: String(item?.label ?? "").trim(),
                valor: toNumber(item?.valor),
                percentual: toNumber(item?.percentual),
            }))
            .filter((item) => item.label && item.valor >= 0);
    } catch (error) {
        console.error("Erro ao processar dados de dimensao:", error);
        return [];
    }
};

const limparGraficosOrfaos = () => {
    for (const [elemento, chart] of chartsPorElemento.entries()) {
        if (!document.body.contains(elemento)) {
            chart.destroy();
            chartsPorElemento.delete(elemento);
        }
    }
};

const getSerieNome = (tipoValor, usarPercentual) =>
    usarPercentual || tipoValor === "PERCENTUAL"
        ? "Participacao (%)"
        : "Quantidade";

const getFormatadorValor = (tipoValor, usarPercentual, casasDecimais = 2) => {
    if (usarPercentual || tipoValor === "PERCENTUAL") {
        return (valor) => `${toNumber(valor).toFixed(casasDecimais)}%`;
    }

    return (valor) => formatadorNumero.format(toNumber(valor));
};

const montarConfig = ({
    titulo,
    tipoGrafico,
    labels,
    valores,
    percentuais,
    tipoValor,
    paleta,
    mostrarValores,
    usarPercentual,
    casasDecimaisPercentual,
}) => {
    const tipoBruto = String(tipoGrafico || "donut")
        .trim()
        .toLowerCase();
    const tipo = tipoBruto === "polararea" || tipoBruto === "polar-area"
        ? "polarArea"
        : tipoBruto === "donut" || tipoBruto === "bar"
          ? tipoBruto
          : "donut";

    const serieValores = usarPercentual ? percentuais : valores;
    const formatarValor = getFormatadorValor(
        tipoValor,
        usarPercentual,
        casasDecimaisPercentual
    );
    const serieNome = getSerieNome(tipoValor, usarPercentual);
    const corPrimaria = paleta[0];

    const base = {
        chart: {
            type: tipo,
            height: 430,
            background: "transparent",
            foreColor: "#1f1f1f",
            toolbar: { show: false },
        },
        title: {
            text: titulo,
            align: "center",
            style: {
                color: corPrimaria,
                fontSize: "20px",
                fontWeight: "bold",
                fontFamily: "Montserrat, sans-serif",
            },
        },
        colors: paleta,
        grid: {
            borderColor: "#e0e0e0",
            strokeDashArray: 4,
        },
        legend: {
            position: "bottom",
            fontSize: "13px",
            labels: { colors: "#4a4a4a" },
        },
        tooltip: {
            y: {
                formatter: (valor, { seriesIndex, dataPointIndex } = {}) => {
                    const idx =
                        Number.isInteger(dataPointIndex) && dataPointIndex >= 0
                            ? dataPointIndex
                            : seriesIndex;
                    const pct = percentuais[idx] ?? 0;
                    const absoluto = valores[idx] ?? 0;

                    if (usarPercentual) {
                        return `${formatadorNumero.format(absoluto)} (${pct.toFixed(2)}%)`;
                    }

                    return `${formatarValor(valor)} (${pct.toFixed(casasDecimaisPercentual)}%)`;
                },
            },
        },
    };

    if (tipo === "bar") {
        const maxPercentual = usarPercentual ? 100 : undefined;

        return {
            ...base,
            series: [{ name: serieNome, data: serieValores }],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 10,
                    columnWidth: "46%",
                },
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        fontSize: "13px",
                        fontWeight: 600,
                        fontFamily: "Montserrat, sans-serif",
                    },
                },
            },
            dataLabels: {
                enabled: mostrarValores,
                formatter: (valor) => formatarValor(valor),
            },
            yaxis: {
                max: maxPercentual,
                min: usarPercentual ? 0 : undefined,
                labels: {
                    formatter: (valor) => formatarValor(valor),
                },
            },
            tooltip: {
                ...base.tooltip,
                x: { show: false },
                marker: { show: false },
                custom: ({ dataPointIndex, w } = {}) => {
                    const idx =
                        Number.isInteger(dataPointIndex) && dataPointIndex >= 0
                            ? dataPointIndex
                            : 0;
                    const tooltipWrapper =
                        w?.globals?.dom?.baseEl?.querySelector(".apexcharts-tooltip");
                    tooltipWrapper?.classList.add("tooltip-grafico-apex-wrapper");
                    const label = labels[idx] ?? "";
                    const pct = percentuais[idx] ?? 0;
                    const absoluto = valores[idx] ?? 0;
                    const texto = usarPercentual
                        ? `${formatadorNumero.format(absoluto)} (${pct.toFixed(2)}%)`
                        : `${formatarValor(absoluto)} (${pct.toFixed(casasDecimaisPercentual)}%)`;

                    return `<div class="tooltip-grafico-apex"><span class="tooltip-grafico-apex__label">${label}:</span> <span class="tooltip-grafico-apex__value">${texto}</span></div>`;
                },
            },
        };
    }

    if (tipo === "polarArea") {
        return {
            ...base,
            chart: {
                ...base.chart,
                type: "polarArea",
            },
            series: serieValores,
            labels,
            stroke: {
                colors: ["#ffffff"],
            },
            fill: {
                opacity: 0.9,
            },
            dataLabels: {
                enabled: mostrarValores,
                formatter: (_, { seriesIndex }) =>
                    `${percentuais[seriesIndex]?.toFixed(casasDecimaisPercentual) ?? "0"}%`,
            },
        };
    }

    return {
        ...base,
        chart: {
            ...base.chart,
            type: "donut",
        },
        series: serieValores,
        labels,
        plotOptions: {
            pie: {
                donut: {
                    size: "55%",
                },
            },
        },
        dataLabels: {
            enabled: mostrarValores,
            formatter: (_, { seriesIndex }) =>
                `${percentuais[seriesIndex]?.toFixed(casasDecimaisPercentual) ?? "0"}%`,
        },
    };
};

const renderizarGrafico = (elemento) => {
    const dados = parseDados(elemento.dataset.dados);
    if (!dados.length) {
        const chartAtual = chartsPorElemento.get(elemento);
        if (chartAtual) {
            chartAtual.destroy();
            chartsPorElemento.delete(elemento);
        }

        delete elemento.dataset.chartAssinatura;
        return;
    }

    const titulo = elemento.dataset.titulo?.trim() || "Distribuicao por dimensao";
    const tipoValor = (elemento.dataset.tipoValor || "ABSOLUTO").toUpperCase();
    const tipoGrafico = (elemento.dataset.tipoGrafico || "donut").toLowerCase();
    const mostrarValores = isTruthyData(elemento.dataset.mostrarValores ?? "1");
    const usarPercentual = isTruthyData(elemento.dataset.usarPercentual ?? "0");
    const casasDecimaisPercentual = Number.isInteger(
        Number(elemento.dataset.casasDecimaisPercentual)
    )
        ? Number(elemento.dataset.casasDecimaisPercentual)
        : 2;
    const assinaturaDados =
        `${elemento.dataset.dados || ""}|${titulo}|${tipoValor}|${tipoGrafico}|${mostrarValores}|${usarPercentual}|${casasDecimaisPercentual}`;

    if (elemento.dataset.chartAssinatura === assinaturaDados) {
        return;
    }

    elemento.dataset.chartAssinatura = assinaturaDados;

    const chartAtual = chartsPorElemento.get(elemento);
    if (chartAtual) {
        chartAtual.destroy();
        chartsPorElemento.delete(elemento);
    }

    const paleta = obterPaletaEngaja();
    const config = montarConfig({
        titulo,
        tipoGrafico,
        labels: dados.map((item) => item.label),
        valores: dados.map((item) => item.valor),
        percentuais: dados.map((item) => item.percentual),
        tipoValor,
        paleta,
        mostrarValores,
        usarPercentual,
        casasDecimaisPercentual,
    });

    const chart = new ApexCharts(elemento, config);
    chart.render();
    chartsPorElemento.set(elemento, chart);
};

const inicializarGraficos = () => {
    limparGraficosOrfaos();
    document.querySelectorAll(CHART_SELECTOR).forEach(renderizarGrafico);
};

const registrarHooksLivewire = () => {
    if (hooksLivewireRegistrados || !window.Livewire?.hook) {
        return;
    }

    hooksLivewireRegistrados = true;

    const hook = window.Livewire.hook.bind(window.Livewire);
    ["message.processed", "morph.updated", "commit"].forEach((nomeHook) => {
        try {
            hook(nomeHook, () => queueMicrotask(inicializarGraficos));
        } catch {
            // Ignora hooks indisponiveis na versao atual do Livewire
        }
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializarGraficos, {
        once: true,
    });
} else {
    inicializarGraficos();
}

document.addEventListener("livewire:init", () => {
    registrarHooksLivewire();
    inicializarGraficos();
});

document.addEventListener("livewire:initialized", () => {
    registrarHooksLivewire();
    inicializarGraficos();
});

document.addEventListener("livewire:navigated", inicializarGraficos);
