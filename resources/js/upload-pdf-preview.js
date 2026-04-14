/**
 * PDF.js preview for document upload step 2 (auth cookie + same-origin URL).
 */
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

const MAX_PAGES = 40;

let activeLoadToken = 0;

function viewerHost() {
    return document.getElementById('upload-pdfjs-viewer');
}

function clearViewer() {
    const host = viewerHost();
    if (host) {
        host.replaceChildren();
    }
}

async function renderPdfFromUrl(url) {
    const host = viewerHost();
    if (!host || !url) {
        return;
    }

    const myToken = ++activeLoadToken;
    host.replaceChildren();
    const wrap = document.createElement('div');
    wrap.className = 'upload-pdfjs-pages d-flex flex-column align-items-center gap-3 p-2 w-100';
    host.appendChild(wrap);

    try {
        const loadingTask = pdfjsLib.getDocument({ url, withCredentials: true });
        const pdf = await loadingTask.promise;

        if (myToken !== activeLoadToken) {
            return;
        }

        const total = Math.min(pdf.numPages, MAX_PAGES);
        for (let i = 1; i <= total; i++) {
            if (myToken !== activeLoadToken) {
                return;
            }
            const page = await pdf.getPage(i);
            const baseViewport = page.getViewport({ scale: 1 });
            const maxW = host.clientWidth > 40 ? host.clientWidth - 32 : 560;
            const scale = Math.min(1.35, maxW / baseViewport.width);
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                continue;
            }
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.className = 'shadow-sm rounded border bg-white';

            const task = page.render({ canvasContext: ctx, viewport });
            await task.promise;
            wrap.appendChild(canvas);
        }

        if (myToken !== activeLoadToken) {
            return;
        }

        if (pdf.numPages > MAX_PAGES) {
            const note = document.createElement('p');
            note.className = 'text-muted small mb-0';
            note.textContent = `… ${pdf.numPages - MAX_PAGES} page(s) non affichée(s)`;
            wrap.appendChild(note);
        }
    } catch (e) {
        if (myToken !== activeLoadToken) {
            return;
        }
        const msg = e?.message || String(e);
        wrap.replaceChildren();
        const err = document.createElement('p');
        err.className = 'text-danger small mb-0 px-2';
        err.textContent = msg;
        wrap.appendChild(err);
    }
}

document.addEventListener('livewire:init', () => {
    Livewire.on('upload-pdf-preview-url', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;
        if (typeof url === 'string' && url.length > 0) {
            void renderPdfFromUrl(url);
        }
    });

    Livewire.on('upload-pdf-preview-clear', () => {
        activeLoadToken++;
        clearViewer();
    });
});
