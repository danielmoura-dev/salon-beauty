{{--
    Componente de recorte de imagem circular.
    Uso: envolva o input e o preview com x-data="imageCropper('id-do-input', 'id-do-preview')"
    e inclua este componente dentro do mesmo escopo Alpine.
--}}

@once
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" integrity="sha512-UtLOu9C7NuThQhuXXrGwx9Jb/z9zPQJctuAgNUBK3Z6kkSYT9wJ+2+dh4ZD5dsKDnuDEQBiHOY0yZ5TMcrcQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
@endonce

{{-- ── Modal de recorte ──────────────────────────────────── --}}
<div x-show="cropOpen"
     x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center p-4"
     style="display:none"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/75" @click="cancelCrop()"></div>

    {{-- Card --}}
    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden"
         @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Ajustar foto de perfil</h3>
            <button type="button" @click="cancelCrop()"
                    class="rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Área de recorte --}}
        <div class="relative bg-gray-950" style="height:320px">
            <img id="crop-image-target" src="" alt="" class="block max-w-full">
        </div>

        {{-- Preview + instruções --}}
        <div class="px-5 py-3 flex items-center gap-4 bg-gray-50 border-t border-gray-100">
            <div id="crop-preview-circle"
                 class="h-14 w-14 shrink-0 rounded-full overflow-hidden border-2 border-primary-400 bg-gray-200">
            </div>
            <p class="text-xs text-gray-500 leading-relaxed">
                Mova e use o scroll do mouse para ajustar o zoom. A área dentro do círculo será salva.
            </p>
        </div>

        {{-- Botões --}}
        <div class="flex gap-3 px-5 py-4">
            <button type="button" @click="cancelCrop()"
                class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Cancelar
            </button>
            <button type="button" @click="confirmCrop()"
                class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                Confirmar foto
            </button>
        </div>
    </div>
</div>

@once
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehQLCzs35HvM5Bsn4Q0v+yA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
function imageCropper(fileInputId, previewId) {
    return {
        cropOpen: false,
        _cropper: null,

        init() {
            const self = this;
            const input = document.getElementById(fileInputId);
            if (input) {
                input.addEventListener('change', function(e) {
                    if (e.target.files && e.target.files[0]) {
                        self._openCrop(e.target.files[0]);
                    }
                });
            }
        },

        _openCrop(file) {
            const self = this;
            const reader = new FileReader();
            reader.onload = function(e) {
                self.cropOpen = true;
                self.$nextTick(function() {
                    const img = document.getElementById('crop-image-target');
                    img.src = e.target.result;
                    if (self._cropper) { self._cropper.destroy(); self._cropper = null; }
                    self._cropper = new Cropper(img, {
                        aspectRatio:          1,
                        viewMode:             1,
                        dragMode:             'move',
                        autoCropArea:         0.9,
                        restore:              false,
                        guides:               false,
                        center:               false,
                        highlight:            false,
                        cropBoxMovable:       false,
                        cropBoxResizable:     false,
                        toggleDragModeOnDblclick: false,
                        preview:              '#crop-preview-circle',
                    });
                });
            };
            reader.readAsDataURL(file);
        },

        confirmCrop() {
            const self = this;
            const canvas = self._cropper.getCroppedCanvas({ width: 400, height: 400 });

            canvas.toBlob(function(blob) {
                // Injeta o arquivo recortado no input original
                try {
                    const file = new File([blob], 'foto.jpg', { type: 'image/jpeg' });
                    const dt   = new DataTransfer();
                    dt.items.add(file);
                    document.getElementById(fileInputId).files = dt.files;
                } catch(e) {
                    // Fallback: navegadores sem suporte a DataTransfer
                    console.warn('DataTransfer não suportado:', e);
                }

                // Atualiza o preview na página
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.innerHTML = '<img src="' + canvas.toDataURL('image/jpeg', 0.9) + '" class="h-full w-full object-cover">';
                }

                self._cropper.destroy();
                self._cropper = null;
                self.cropOpen = false;
            }, 'image/jpeg', 0.92);
        },

        cancelCrop() {
            document.getElementById(fileInputId).value = '';
            if (this._cropper) { this._cropper.destroy(); this._cropper = null; }
            this.cropOpen = false;
        }
    };
}
</script>
@endonce
