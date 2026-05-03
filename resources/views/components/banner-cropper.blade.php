{{--
    Componente de edição de banner: upload com recorte 3:1 OU escolha de cor.
    Uso: envolva com x-data="bannerCropper('banner-input', 'banner-preview', 'banner-color-input')"
--}}

@once
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endonce

{{-- Modal de edição do banner --}}
<div x-show="bannerOpen"
     x-cloak
     class="fixed inset-0 z-[200] flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-black/75" @click="cancelBanner()"></div>

    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden z-10">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Editar banner</h3>
            <button type="button" @click="cancelBanner()"
                    class="rounded-lg p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-100">
            <button type="button" @click="bannerTab = 'image'"
                :class="bannerTab === 'image' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                class="flex-1 py-2.5 text-sm font-medium border-b-2 transition-colors">
                Imagem
            </button>
            <button type="button" @click="bannerTab = 'color'"
                :class="bannerTab === 'color' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                class="flex-1 py-2.5 text-sm font-medium border-b-2 transition-colors">
                Cor sólida
            </button>
        </div>

        {{-- Aba Imagem --}}
        <div x-show="bannerTab === 'image'">
            <div class="bg-gray-950" style="height:240px;overflow:hidden;">
                <img id="banner-crop-target" src="" alt="" style="display:block;max-width:100%;">
            </div>
            <div class="px-5 py-3 flex items-center gap-4 bg-gray-50 border-t border-gray-100">
                <div id="banner-crop-preview"
                     class="h-10 w-32 shrink-0 rounded-lg overflow-hidden border-2 border-primary-400 bg-gray-200">
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Mova e use o scroll para ajustar o zoom. Proporção 3:1.
                </p>
            </div>
            <div class="flex gap-3 px-5 py-4">
                <button type="button" @click="cancelBanner()"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" @click="confirmBannerCrop()"
                    class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                    Confirmar imagem
                </button>
            </div>
        </div>

        {{-- Aba Cor --}}
        <div x-show="bannerTab === 'color'" class="px-5 py-5 space-y-4">
            <p class="text-xs text-gray-500">Escolha uma cor para o banner do seu link público.</p>
            <div class="grid grid-cols-6 gap-2">
                @foreach(['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316','#eab308','#22c55e','#14b8a6','#0ea5e9','#3b82f6','#1e293b','#6b7280'] as $color)
                    <button type="button" @click="pickColor('{{ $color }}')"
                        :class="selectedColor === '{{ $color }}' ? 'ring-2 ring-offset-2 ring-primary-600 scale-110' : ''"
                        style="background-color: {{ $color }}"
                        class="h-10 w-full rounded-xl transition-transform">
                    </button>
                @endforeach
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1.5">Cor personalizada</label>
                <div class="flex items-center gap-2">
                    <input type="color" :value="selectedColor" @input="pickColor($event.target.value)"
                        class="h-10 w-14 rounded-xl border border-gray-200 cursor-pointer p-0.5">
                    <span class="text-sm font-mono text-gray-600" x-text="selectedColor"></span>
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" @click="cancelBanner()"
                    class="flex-1 rounded-xl border border-gray-300 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" @click="confirmBannerColor()"
                    class="flex-1 rounded-xl bg-primary-600 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                    Confirmar cor
                </button>
            </div>
        </div>
    </div>
</div>

@once
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
function bannerCropper(fileInputId, previewId, colorInputId, autoSubmitFormId) {
    return {
        bannerOpen: false,
        bannerTab:  'image',
        selectedColor: '#6366f1',
        _bannerCropper: null,

        init() {
            const self = this;
            const input = document.getElementById(fileInputId);
            if (!input) return;
            input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    self._openBannerCrop(e.target.files[0]);
                }
            });
        },

        openBannerModal() {
            this.bannerTab  = 'image';
            this.bannerOpen = true;
        },

        _openBannerCrop(file) {
            const self   = this;
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('banner-crop-target');
                img.src   = e.target.result;
                self.bannerTab  = 'image';
                self.bannerOpen = true;
                setTimeout(function() {
                    if (self._bannerCropper) { self._bannerCropper.destroy(); self._bannerCropper = null; }
                    self._bannerCropper = new Cropper(img, {
                        aspectRatio:              3,
                        viewMode:                 1,
                        dragMode:                 'move',
                        autoCropArea:             0.9,
                        restore:                  false,
                        guides:                   true,
                        highlight:                false,
                        cropBoxMovable:           false,
                        cropBoxResizable:         false,
                        toggleDragModeOnDblclick: false,
                        preview:                  '#banner-crop-preview',
                    });
                }, 200);
            };
            reader.readAsDataURL(file);
        },

        pickColor(hex) {
            this.selectedColor = hex;
        },

        confirmBannerCrop() {
            const self = this;
            if (!self._bannerCropper) return;
            const canvas = self._bannerCropper.getCroppedCanvas({ width: 1200, height: 400 });
            if (!canvas) return;
            canvas.toBlob(function(blob) {
                try {
                    const file = new File([blob], 'banner.jpg', { type: 'image/jpeg' });
                    const dt   = new DataTransfer();
                    dt.items.add(file);
                    document.getElementById(fileInputId).files = dt.files;
                } catch(err) { console.warn(err); }

                // Atualiza preview
                const preview = document.getElementById(previewId);
                if (preview) {
                    const url = canvas.toDataURL('image/jpeg', 0.92);
                    preview.style.backgroundImage = 'url(' + url + ')';
                    preview.style.backgroundColor = '';
                }

                // Limpa cor
                const colorInput = document.getElementById(colorInputId);
                if (colorInput) colorInput.value = '';

                self._bannerCropper.destroy();
                self._bannerCropper = null;
                self.bannerOpen     = false;

                if (autoSubmitFormId) {
                    const form = document.getElementById(autoSubmitFormId);
                    if (form) form.submit();
                }
            }, 'image/jpeg', 0.92);
        },

        confirmBannerColor() {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.style.backgroundColor = this.selectedColor;
                preview.style.backgroundImage = '';
            }
            // Limpa arquivo
            const input = document.getElementById(fileInputId);
            if (input) input.value = '';
            // Seta cor no campo hidden
            const colorInput = document.getElementById(colorInputId);
            if (colorInput) colorInput.value = this.selectedColor;

            this.bannerOpen = false;

            if (autoSubmitFormId) {
                const form = document.getElementById(autoSubmitFormId);
                if (form) form.submit();
            }
        },

        cancelBanner() {
            const input = document.getElementById(fileInputId);
            if (input) input.value = '';
            if (this._bannerCropper) { this._bannerCropper.destroy(); this._bannerCropper = null; }
            this.bannerOpen = false;
        },
    };
}
</script>
@endonce
