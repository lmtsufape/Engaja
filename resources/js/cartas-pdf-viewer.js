import * as pdfjsLib from "pdfjs-dist";
import workerSrc from "pdfjs-dist/build/pdf.worker.min.mjs?url";

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

// Largura de renderizacao das paginas (px CSS). O canvas escala para 100% do container.
const LARGURA_ALVO = 1100;

/**
 * Envia o resultado de uma etapa (download ou render) para o log do servidor.
 * Sem isso, a falha ficaria so no console do navegador do usuario.
 */
const registrarEtapa = (contexto, etapa, sucesso, detalhe) => {
    const linha = `[cartas.pdf] ${etapa}: ${sucesso ? "OK" : "FALHOU"} — ${detalhe}`;
    sucesso ? console.info(linha) : console.error(linha);

    if (!contexto.diagUrl) {
        return;
    }

    // keepalive garante a entrega mesmo se o usuario fechar a aba no meio.
    fetch(contexto.diagUrl, {
        method: "POST",
        credentials: "same-origin",
        keepalive: true,
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        },
        body: JSON.stringify({
            etapa,
            sucesso,
            detalhe: String(detalhe).slice(0, 500),
            mensagem_id: contexto.mensagemId ? Number(contexto.mensagemId) : null,
        }),
    }).catch(() => {
        /* falha ao logar nao pode quebrar a visualizacao */
    });
};

/**
 * Baixa o PDF e confere o que o servidor realmente devolveu: sessao expirada e
 * erro de permissao chegam como HTML ou redirect, nao como PDF corrompido.
 */
const baixarPdf = async (contexto, src, ms) => {
    const resposta = await fetch(src, { credentials: "same-origin" });

    if (!resposta.ok) {
        registrarEtapa(
            contexto,
            "download",
            false,
            `HTTP ${resposta.status} ${resposta.statusText}`
        );

        return null;
    }

    const buffer = await resposta.arrayBuffer();
    const assinatura = Array.from(new Uint8Array(buffer.slice(0, 5)))
        .map((b) => String.fromCharCode(b))
        .join("");
    const kb = (buffer.byteLength / 1024).toFixed(1);
    const tipo = resposta.headers.get("content-type");

    if (!assinatura.startsWith("%PDF-")) {
        registrarEtapa(
            contexto,
            "download",
            false,
            `recebido ${kb} KB de ${tipo}, mas o conteudo nao comeca com %PDF-`
        );

        return null;
    }

    registrarEtapa(contexto, "download", true, `${kb} KB em ${ms()}ms`);

    return buffer;
};

const renderizarDocumento = async (container) => {
    const src = container.dataset.pdfSrc;
    const contexto = {
        diagUrl: container.dataset.diagUrl || null,
        mensagemId: container.dataset.mensagemId || null,
    };

    if (!src) {
        return;
    }

    const inicio = performance.now();
    const ms = () => Math.round(performance.now() - inicio);

    container.classList.add("is-loading");

    try {
        const buffer = await baixarPdf(contexto, src, ms);

        // Se o download falhou, ainda tentamos pela url para nao regredir o
        // comportamento; o erro do pdf.js e registrado no catch abaixo.
        const pdf = await pdfjsLib.getDocument(
            buffer
                ? { data: new Uint8Array(buffer) }
                : { url: src, withCredentials: true }
        ).promise;

        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        container.classList.remove("is-loading");
        container.replaceChildren();

        for (let numero = 1; numero <= pdf.numPages; numero += 1) {
            const pagina = await pdf.getPage(numero);
            const base = pagina.getViewport({ scale: 1 });
            const escala = (LARGURA_ALVO / base.width) * dpr;
            const viewport = pagina.getViewport({ scale: escala });

            const canvas = document.createElement("canvas");
            canvas.className = "cpe-letter-page";
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.setAttribute("role", "img");
            canvas.setAttribute("aria-label", `Página ${numero} de ${pdf.numPages}`);
            container.appendChild(canvas);

            await pagina.render({
                canvasContext: canvas.getContext("2d"),
                viewport,
            }).promise;
        }

        registrarEtapa(
            contexto,
            "render",
            true,
            `${pdf.numPages} pagina(s) em ${ms()}ms`
        );
    } catch (erro) {
        container.classList.remove("is-loading");
        container.replaceChildren();
        const aviso = document.createElement("div");
        aviso.className = "cpe-letter-doc__error";
        aviso.textContent = "Não foi possível carregar a carta.";
        container.appendChild(aviso);

        registrarEtapa(
            contexto,
            "render",
            false,
            `${erro?.name ?? "Erro"}: ${erro?.message ?? erro}`
        );
    }
};

const init = () => {
    document.querySelectorAll(".cpe-letter-doc[data-pdf-src]").forEach((container) => {
        if (container.dataset.pdfRendered) {
            return;
        }

        container.dataset.pdfRendered = "1";
        renderizarDocumento(container);
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
} else {
    init();
}
