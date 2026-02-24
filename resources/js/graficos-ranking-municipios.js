import ApexCharts from "apexcharts";
import { getGraficoPadraoConfig } from "./grafico-template";

const CHART_SELECTOR = '[data-chart="ranking-municipios"]';
const chartsPorElemento = new Map();
let hooksLivewireRegistrados = false;

const formatadorAbsoluto = new Intl.NumberFormat("pt-BR", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

const obterCorPadrao = (corBruta) => {
    const css = getComputedStyle(document.documentElement);
    const fallback = "#421944";
    const cor = String(corBruta ?? "").trim();

    if (!cor) {
        return css.getPropertyValue("--engaja-purple").trim() || fallback;
    }

    if (cor.startsWith("--")) {
        return css.getPropertyValue(cor).trim() || fallback;
    }

    return cor;
};

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
            .map((item) => {
                const percentual = Number(item?.percentual);
                const absoluto = Number(item?.absoluto);
                const valor = Number(item?.valor);

                return {
                    municipio: String(item?.municipio ?? "").trim(),
                    percentual: Number.isFinite(percentual) ? percentual : null,
                    absoluto: Number.isFinite(absoluto) ? absoluto : null,
                    valor: Number.isFinite(valor) ? valor : null,
                };
            })
            .filter(
                (item) =>
                    item.municipio &&
                    (item.percentual !== null || item.valor !== null)
            );
    } catch (error) {
        console.error("Erro ao processar dados do grafico de ranking:", error);
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

    const titulo = elemento.dataset.titulo?.trim() || "Ranking de Municipios";
    const tipoValor = (elemento.dataset.tipoValor || "PERCENTUAL").toUpperCase();
    const labelIndicadorPercentual =
        elemento.dataset.labelIndicadorPercentual?.trim() || "Taxa";
    const labelIndicadorAbsoluto =
        elemento.dataset.labelIndicadorAbsoluto?.trim() || "Quantidade";
    const cor = obterCorPadrao(elemento.dataset.cor);
    const possuiIndicadorComposto = dados.some(
        (item) => item.percentual !== null
    );
    const assinaturaDados = `${elemento.dataset.dados || ""}|${titulo}|${tipoValor}|${labelIndicadorPercentual}|${labelIndicadorAbsoluto}|${cor}`;

    if (elemento.dataset.chartAssinatura === assinaturaDados) {
        return;
    }

    elemento.dataset.chartAssinatura = assinaturaDados;

    const chartAtual = chartsPorElemento.get(elemento);
    if (chartAtual) {
        chartAtual.destroy();
        chartsPorElemento.delete(elemento);
    }

    const percentual = tipoValor === "PERCENTUAL";
    const valoresSerie = possuiIndicadorComposto
        ? dados.map((item) => Number(item.percentual ?? 0))
        : dados.map((item) => Number(item.valor ?? 0));

    const formatarValor = possuiIndicadorComposto
        ? (valor) => `${Number(valor).toFixed(2)}%`
        : percentual
          ? (valor) => `${Number(valor).toFixed(2)}%`
          : (valor) => formatadorAbsoluto.format(Number(valor));

    const config = getGraficoPadraoConfig({
        titulo,
        categorias: dados.map((item) => item.municipio),
        valores: valoresSerie,
        cor,
        altura: 500,
        tipo: "bar",
        horizontal: true,
        nomeSerie: possuiIndicadorComposto
            ? `${labelIndicadorPercentual} (%)`
            : percentual
              ? "Valor (%)"
              : "Valor",
        formatarValor,
    });

    if (possuiIndicadorComposto) {
        config.tooltip = {
            y: {
                formatter: (val, { dataPointIndex } = {}) => {
                    const idx =
                        Number.isInteger(dataPointIndex) && dataPointIndex >= 0
                            ? dataPointIndex
                            : 0;
                    const percentualItem = Number(
                        dados[idx]?.percentual ?? val ?? 0
                    );
                    const absolutoItem = dados[idx]?.absoluto;

                    if (absolutoItem === null || absolutoItem === undefined) {
                        return `${percentualItem.toFixed(2)}%`;
                    }

                    return `${percentualItem.toFixed(2)}% | ${labelIndicadorAbsoluto}: ${formatadorAbsoluto.format(
                        Number(absolutoItem)
                    )}`;
                },
            },
        };

        config.dataLabels = {
            ...config.dataLabels,
            formatter: (val, { dataPointIndex } = {}) => {
                const idx =
                    Number.isInteger(dataPointIndex) && dataPointIndex >= 0
                        ? dataPointIndex
                        : 0;
                const percentualItem = Number(
                    dados[idx]?.percentual ?? val ?? 0
                );
                return `${percentualItem.toFixed(2)}%`;
            },
        };
    }

    const chart = new ApexCharts(elemento, config);
    chart.render();
    chartsPorElemento.set(elemento, chart);
};

const inicializarGraficosRanking = () => {
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
            hook(nomeHook, () => queueMicrotask(inicializarGraficosRanking));
        } catch {
            // Ignora hooks indisponiveis na versao atual do Livewire
        }
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", inicializarGraficosRanking, {
        once: true,
    });
} else {
    inicializarGraficosRanking();
}

document.addEventListener("livewire:init", () => {
    registrarHooksLivewire();
    inicializarGraficosRanking();
});

document.addEventListener("livewire:initialized", () => {
    registrarHooksLivewire();
    inicializarGraficosRanking();
});

document.addEventListener("livewire:navigated", inicializarGraficosRanking);
