// Template de estilo para gráficos ApexCharts
// Reutilize este objeto para padronizar todos os gráficos do BI

export function getGraficoPadraoConfig({
    titulo = "Gráfico BI",
    categorias = [],
    valores = [],
    cor = "#421944",
    altura = 500,
    tipo = "bar",
    horizontal = true,
    nomeSerie = "Valor",
    formatarValor = (valor) => {
        const numero = Number(valor);
        return Number.isFinite(numero) ? numero.toFixed(2) : "-";
    },
} = {}) {
    return {
        chart: {
            type: tipo,
            height: altura,
            background: "transparent",
            foreColor: "#1f1f1f",
            toolbar: { show: false },
        },
        title: {
            text: titulo,
            align: "center",
            style: {
                color: cor,
                fontSize: "22px",
                fontWeight: "bold",
                fontFamily: "Montserrat, sans-serif",
            },
        },
        plotOptions: {
            bar: {
                horizontal: horizontal,
                borderRadius: 6,
                barHeight: "80%",
            },
        },
        series: [{ name: nomeSerie, data: valores }],
        xaxis: {
            categories: categorias,
            labels: {
                style: {
                    colors: categorias.map(() => cor),
                    fontSize: "15px",
                    fontWeight: 600,
                    fontFamily: "Montserrat, sans-serif",
                },
            },
        },
        colors: [cor],
        tooltip: {
            y: {
                formatter: (val) => formatarValor(val),
            },
        },
        dataLabels: {
            enabled: true,
            formatter: (val) => formatarValor(val),
            offsetX: 6,
            style: {
                colors: [cor],
                fontSize: "15px",
                fontWeight: 700,
                fontFamily: "Montserrat, sans-serif",
            },
            background: {
                enabled: true,
                foreColor: "#ffffff",
                borderRadius: 6,
                padding: 5,
                opacity: 0.96,
                borderWidth: 1,
                borderColor: "#2f1432",
            },
        },
        grid: {
            borderColor: "#e0e0e0",
            strokeDashArray: 4,
        },
    };
}
