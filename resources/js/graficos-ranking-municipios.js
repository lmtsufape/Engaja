import ApexCharts from "apexcharts";
import { getGraficoPadraoConfig } from "./grafico-template";

document.addEventListener("DOMContentLoaded", function () {
    const chartDiv = document.querySelector("#rankingMunicipiosChart");
    if (chartDiv && chartDiv.dataset.dados) {
        const dados = JSON.parse(chartDiv.dataset.dados);
        const categorias = dados.map((item) => item.municipio);
        const valores = dados.map((item) => item.valor);
        const cor =
            getComputedStyle(document.documentElement)
                .getPropertyValue("--engaja-purple")
                .trim() || "#421944";
        // Lê o título do atributo data-titulo, se existir
        let titulo = chartDiv.dataset.titulo;
        if (!titulo || titulo === "null") {
            titulo = "Ranking de Analfabetismo por Municípios";
        }
        // Aplica estilos ao container
        chartDiv.style.background = "#f8f9fa"; // cor clara
        chartDiv.style.borderRadius = "18px";
        chartDiv.style.boxShadow = "0 4px 16px rgba(66,25,68,0.10)";
        chartDiv.style.padding = "32px 24px";
        chartDiv.style.margin = "24px 0";
        chartDiv.style.minHeight = "520px";
        const config = getGraficoPadraoConfig({
            titulo,
            categorias,
            valores,
            cor,
            altura: 500,
            tipo: "bar",
            horizontal: true,
            nomeSerie: "Valor (%)",
        });
        const chart = new ApexCharts(chartDiv, config);
        chart.render();
    }
});
