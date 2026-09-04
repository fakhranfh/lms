{{-- Expects an ancestor x-data exposing videoPreviewUrl/closeVideoPreview and filePreviewUrl/filePreviewName/closeFilePreview (see resources/js/rte-video-preview.js). --}}
<x-ui.modal show="videoPreviewUrl" onClose="closeVideoPreview()" max-width="max-w-3xl" backdrop="bg-black/80">
    <button type="button" @click="closeVideoPreview()" class="absolute -top-10 right-0 text-white hover:text-white/80">
        <span class="material-symbols-outlined">close</span>
    </button>
    <video x-show="videoPreviewUrl" :src="videoPreviewUrl" controls autoplay class="w-full max-h-[80vh] rounded-lg bg-black"></video>
</x-ui.modal>

<x-ui.modal show="filePreviewUrl" onClose="closeFilePreview()" max-width="max-w-4xl" backdrop="bg-black/80">
    <div class="flex items-center justify-between mb-2">
        <p class="text-body-sm font-medium text-white truncate" x-text="filePreviewName"></p>
        <div class="flex items-center gap-2 shrink-0">
            <a :href="filePreviewUrl" target="_blank" rel="noopener" class="text-white hover:text-white/80">
                <span class="material-symbols-outlined">open_in_new</span>
            </a>
            <button type="button" @click="closeFilePreview()" class="text-white hover:text-white/80">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>
    <iframe x-show="filePreviewUrl" :src="filePreviewUrl" class="w-full h-[80vh] rounded-lg bg-white"></iframe>
</x-ui.modal>
