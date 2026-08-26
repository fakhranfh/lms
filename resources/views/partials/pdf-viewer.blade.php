{{--
    Reusable PDF viewer: renders the document page-by-page onto a <canvas>
    (see partials.pdf-reader-script) with a left sidebar listing every page
    for quick navigation.

    Required prop:
    - $pdfUrlExpression: an Alpine JS expression (string) that resolves to
      the PDF's URL in the enclosing component's scope, e.g. 'viewingMaterial.url'
      or 'viewingReferenceFile.url'.
--}}
<div
    x-data="{
        pdfLoaded: false,
        pdfLoadedUrl: null,
        pdfPage: 1,
        pdfPageCount: 0,
        pdfPageInput: '1',
        pdfLoading: false,
        pdfError: null,
        async loadPdf(url) {
            if (this.pdfLoadedUrl === url) { return; }
            this.pdfLoadedUrl = url;
            this.pdfLoaded = false;
            this.pdfPage = 1;
            this.pdfPageInput = '1';
            this.pdfPageCount = 0;
            this.pdfError = null;
            this.pdfLoading = true;

            try {
                const { numPages } = await window.loadPdfIntoContainer(this.$refs.pdfCanvas, url);
                this.pdfLoaded = true;
                this.pdfPageCount = numPages;
                await this.renderCurrentPage();
            } catch (e) {
                this.pdfError = e.message || 'Failed to load PDF';
            } finally {
                this.pdfLoading = false;
            }
        },
        async renderCurrentPage() {
            if (! this.pdfLoaded) { return; }
            try {
                await window.renderPdfPage(this.$refs.pdfCanvas, this.pdfPage);
            } catch (e) {
                this.pdfError = e.message || 'Failed to render page';
            }
        },
        goToPage(page) {
            const target = Math.min(Math.max(parseInt(page) || 1, 1), this.pdfPageCount);
            this.pdfPageInput = String(target);
            if (target === this.pdfPage) { return; }
            this.pdfPage = target;
            this.renderCurrentPage();
        },
    }"
    x-effect="loadPdf({{ $pdfUrlExpression }})"
    class="w-full h-full flex"
>
    <div
        x-show="pdfPageCount > 1"
        x-cloak
        class="w-16 flex-shrink-0 overflow-y-auto border-r border-outline-variant bg-surface py-space-sm"
    >
        <template x-for="page in pdfPageCount" :key="page">
            <button
                type="button"
                @click="goToPage(page)"
                :class="page === pdfPage ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container'"
                class="w-full py-space-xs font-label-sm text-label-sm transition"
                x-text="page"
            ></button>
        </template>
    </div>

    <div class="flex-1 min-w-0 flex flex-col">
        <div
            x-show="pdfPageCount > 1"
            x-cloak
            class="flex items-center justify-center gap-space-sm px-space-lg py-space-sm border-b border-outline-variant flex-shrink-0 bg-surface"
        >
            <button
                type="button"
                @click="goToPage(pdfPage - 1)"
                :disabled="pdfPage <= 1"
                class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition disabled:opacity-50"
            >
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </button>

            <form @submit.prevent="goToPage(pdfPageInput)" class="flex items-center gap-space-xs">
                <span class="text-body-sm text-on-surface-variant">Page</span>
                <input
                    type="number"
                    min="1"
                    :max="pdfPageCount"
                    x-model="pdfPageInput"
                    @blur="goToPage(pdfPageInput)"
                    class="w-16 px-space-sm py-1 border border-outline rounded-lg font-body-sm text-body-sm text-center focus:outline-none focus:ring-2 focus:ring-primary/50"
                />
                <span class="text-body-sm text-on-surface-variant" x-text="'of ' + pdfPageCount"></span>
            </form>

            <button
                type="button"
                @click="goToPage(pdfPage + 1)"
                :disabled="pdfPage >= pdfPageCount"
                class="px-space-sm py-space-xs border border-outline rounded-lg font-label-sm text-label-sm text-on-surface hover:bg-surface-container transition disabled:opacity-50"
            >
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </button>
        </div>

        <div class="flex-1 overflow-auto p-space-lg">
            <p x-show="pdfLoading" x-cloak class="text-center text-on-surface-variant">Loading PDF...</p>
            <p x-show="pdfError" x-cloak class="text-center text-error" x-text="pdfError"></p>
            <div x-show="! pdfLoading && ! pdfError" x-cloak x-ref="pdfCanvas"></div>
        </div>
    </div>
</div>
