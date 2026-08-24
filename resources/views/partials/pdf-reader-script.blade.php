<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/legacy/build/pdf.min.js"></script>
<script>
    if (! window.loadPdfIntoContainer) {
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/legacy/build/pdf.worker.min.js';
        }

        // Keyed by the <canvas> container element, entirely outside Alpine's
        // reactive state. pdf.js's PDFDocumentProxy/PDFPageProxy rely on
        // ES2022 private class fields (#d etc). If the document object is
        // ever assigned to an Alpine x-data property (e.g. `this.pdf = ...`),
        // Alpine's reactivity wraps it in its own Proxy — and calling a
        // private-field method through that Proxy throws "Cannot read
        // private member #d from an object whose class did not declare it",
        // because `this` inside the method is the Proxy, not the real
        // instance. Keeping the document out of Alpine entirely sidesteps
        // this; only plain data (page numbers, counts, error strings)
        // should ever be stored on Alpine components.
        const pdfDocuments = new WeakMap();

        // Tracks the in-flight pdf.js RenderTask per container, so a fast
        // next/prev click can cancel the still-running previous render
        // before starting a new one.
        const activeRenderTasks = new WeakMap();

        // Renders one page at a time onto a <canvas> inside `container`,
        // instead of an <embed>/native PDF plugin. The native plugin runs
        // in its own focusable browsing context, so a click inside it
        // fires a top-level `window blur` — which the proctor page reads
        // as a tab-switch violation. Canvas rendering keeps everything in
        // the main document, so focus never leaves it.
        window.loadPdfIntoContainer = async function(container, url) {
            if (! window.pdfjsLib) {
                throw new Error('PDF viewer failed to load. Check your connection and try again.');
            }

            // Fetch the bytes ourselves (same plain fetch() already used
            // for txt/markdown previews on this page, which is known to
            // work against R2) rather than letting pdf.js request the URL
            // directly — pdf.js defaults to HTTP Range requests, which
            // need extra CORS response headers (Access-Control-Allow-
            // Headers: Range, Access-Control-Expose-Headers: Content-
            // Range/Accept-Ranges/Content-Length) that the R2 bucket
            // doesn't send, so the fetch silently fails and the viewer is
            // left blank instead of erroring visibly.
            const response = await fetch(url);
            if (! response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            const data = await response.arrayBuffer();

            const pdf = await pdfjsLib.getDocument({ data }).promise;
            pdfDocuments.set(container, pdf);

            return { numPages: pdf.numPages };
        };

        window.renderPdfPage = async function(container, pageNumber) {
            const pdf = pdfDocuments.get(container);
            if (! pdf) {
                throw new Error('PDF not loaded yet');
            }

            const token = Symbol('renderPdfPage');
            container.dataset.renderToken = token.toString();

            const previousTask = activeRenderTasks.get(container);
            if (previousTask) {
                previousTask.cancel();
            }

            const page = await pdf.getPage(pageNumber);
            const viewport = page.getViewport({ scale: 1.5 });

            if (container.dataset.renderToken !== token.toString()) { return; }

            container.innerHTML = '';
            const canvas = document.createElement('canvas');
            canvas.className = 'max-w-full h-auto mx-auto block shadow rounded bg-white';
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            container.appendChild(canvas);

            const renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport });
            activeRenderTasks.set(container, renderTask);

            try {
                await renderTask.promise;
            } catch (e) {
                if (e?.name === 'RenderingCancelledException') { return; }
                throw e;
            } finally {
                if (activeRenderTasks.get(container) === renderTask) {
                    activeRenderTasks.delete(container);
                }
            }
        };
    }
</script>
