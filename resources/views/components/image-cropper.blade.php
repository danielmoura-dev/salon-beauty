{{--
    Componente de recorte de imagem circular.
    Uso: envolva o input e o preview com x-data="imageCropper('id-do-input', 'id-do-preview')"
    e inclua este componente dentro do mesmo escopo Alpine.
--}}

@once
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endonce

{{-- ── Modal de recorte ──────────────────────────────────── --}}
<div x-show="cropOpen"
     x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/75" @click="cancelCrop()"></div>

    {{-- Card --}}
    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden z-10">

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
        <div class="bg-gray-950" style="height:300px;overflow:hidden;">
            <img id="crop-image-target" src="" alt=""
                 style="display:block;max-width:100%;max-height:300px;">
        </div>

        {{-- Preview + instruções --}}
        <div class="px-5 py-3 flex items-center gap-4 bg-gray-50 border-t border-gray-100">
            <div id="crop-preview-circle"
                 class="h-14 w-14 shrink-0 rounded-full overflow-hidden border-2 border-primary-400 bg-gray-200">
            </div>
            <p class="text-xs text-gray-500 leading-relaxed">
                Mova a foto e use o scroll para ajustar o zoom.
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
function imageCropper(fileInputId, previewId, autoSubmitFormId) {
    return {
        cropOpen: false,
        _cropper: null,

        init() {
            const self = this;
            const input = document.getElementById(fileInputId);
            if (!input) return;
            input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    self._openCrop(e.target.files[0]);
                }
            });
        },

        _openCrop(file) {
            const self = this;
            const reader = new FileReader();
            reader.onload = function(e) {
                // 1. Seta a src na imagem antes de abrir o modal
                const img = document.getElementById('crop-image-target');
                img.src = e.target.result;

                // 2. Abre o modal
                self.cropOpen = true;

                // 3. Aguarda o modal renderizar + animação terminar antes de iniciar o Cropper
                setTimeout(function() {
                    if (self._cropper) {
                        self._cropper.destroy();
                        self._cropper = null;
                    }
                    self._cropper = new Cropper(img, {
                        aspectRatio:              1,
                        viewMode:                 1,
                        dragMode:                 'move',
                        autoCropArea:             0.85,
                        restore:                  false,
                        guides:                   true,
                        center:                   true,
                        highlight:                false,
                        cropBoxMovable:           false,
                        cropBoxResizable:         false,
                        toggleDragModeOnDblclick: false,
                        preview:                  '#crop-preview-circle',
                    });
                }, 200);
            };
            reader.readAsDataURL(file);
        },

        confirmCrop() {
            const self = this;
            if (!self._cropper) return;

            const canvas = self._cropper.getCroppedCanvas({ width: 400, height: 400 });
            if (!canvas) return;

            canvas.toBlob(function(blob) {
                // Injeta o arquivo recortado no input original via DataTransfer
                try {
                    const file = new File([blob], 'foto.jpg', { type: 'image/jpeg' });
                    const dt   = new DataTransfer();
                    dt.items.add(file);
                    document.getElementById(fileInputId).files = dt.files;
                } catch(err) {
                    console.warn('DataTransfer não suportado:', err);
                }

                // Atualiza o preview na página
                const preview = document.getElementById(previewId);
                if (preview) {
                    const url = canvas.toDataURL('image/jpeg', 0.92);
                    preview.innerHTML = '<img src="' + url + '" class="h-full w-full object-cover">';
                }

                self._cropper.destroy();
                self._cropper = null;
                self.cropOpen = false;

                if (autoSubmitFormId) {
                    const form = document.getElementById(autoSubmitFormId);
                    if (form) form.submit();
                }
            }, 'image/jpeg', 0.92);
        },

        cancelCrop() {
            const input = document.getElementById(fileInputId);
            if (input) input.value = '';
            if (this._cropper) {
                this._cropper.destroy();
                this._cropper = null;
            }
            this.cropOpen = false;
        }
    };
}
</script>
@endonce
