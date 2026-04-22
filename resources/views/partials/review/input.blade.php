<div class="flex-1 flex items-start justify-center p-6 relative">

    {{-- Fullscreen loading overlay tijdens upload + extractie --}}
    <div wire:loading wire:target="upload"
         class="absolute inset-0 bg-white/95 backdrop-blur-sm z-20 flex items-center justify-center"
         x-data="{
            steps: [
                'Bestand uploaden…',
                'Document ontleden…',
                'Tabellen en lijsten verwerken…',
                'Tekst opschonen…',
            ],
            i: 0, visible: true,
            get text() { return this.steps[this.i] ?? '' },
            advance() {
                if (this.i >= this.steps.length - 1) return;
                this.visible = false;
                setTimeout(() => {
                    this.i++;
                    this.visible = true;
                    setTimeout(() => this.advance(), 1400);
                }, 300);
            }
         }"
         x-init="setTimeout(() => advance(), 1400)">
        <div class="max-w-sm text-center p-8">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-[#E2E8F0] border-t-[#1A4F82] mb-5"></div>
            <h3 class="text-base font-semibold text-[#0F172A] mb-2">Document inlezen</h3>
            <div class="relative h-5 overflow-hidden">
                <p class="text-sm text-[#1A4F82] font-medium transition-all duration-300"
                   :style="visible ? 'opacity:1;transform:translateY(0)' : 'opacity:0;transform:translateY(-6px)'"
                   x-text="text">
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-2xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-6">
        <h2 class="text-lg font-semibold text-[#0F172A] mb-1">Dossier invoeren</h2>
        <p class="text-sm text-[#64748B] mb-5">Upload een PDF/Word-bestand of plak de tekst direct.</p>

        <div class="grid md:grid-cols-2 gap-3 mb-4">
            <label class="flex flex-col justify-center rounded-[6px] border-2 border-dashed border-[#CBD5E1] bg-[#F5F9FF] hover:bg-[#EBF3FC] p-4 cursor-pointer transition">
                <span class="text-sm font-medium text-[#334155]">Upload bestand</span>
                <span class="text-xs text-[#64748B] mt-1">PDF, DOCX, DOC of TXT (max 10 MB)</span>
                <input type="file" wire:model="upload" accept=".pdf,.docx,.doc,.txt,.md" class="mt-2 text-xs text-[#334155]" />
            </label>

            <button type="button" wire:click="loadExample"
                class="rounded-[6px] border border-[#E2E8F0] bg-[#F1F5F9] hover:bg-[#E2E8F0] p-4 text-left transition">
                <span class="block text-sm font-medium text-[#334155]">Gebruik demo-dossier</span>
                <span class="block text-xs text-[#64748B] mt-1">Mannelijke patiënt 74 jaar met polyfarmacie — geschikt voor demo</span>
            </button>
        </div>

        <label class="block text-sm font-medium text-[#334155] mb-2">Dossiertekst</label>
        <textarea wire:model.blur="dossierText" rows="14"
            placeholder="Plak hier het geanonimiseerde dossier..."
            class="block w-full rounded-[5px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-mono text-[#0F172A] focus:border-[#1A4F82] focus:ring-1 focus:ring-[#1A4F82] outline-none"></textarea>

        @if ($errorMessage)
            <div class="mt-4 rounded-[5px] border border-red-200 bg-[#FEF2F2] px-4 py-3 text-sm text-[#991B1B]">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <button type="button" wire:click="analyse"
                wire:loading.attr="disabled"
                wire:target="analyse"
                class="inline-flex items-center gap-2 rounded-[5px] bg-[#1A4F82] hover:bg-[#1D5FA0] disabled:bg-[#64748B] disabled:cursor-wait text-white text-sm font-medium px-5 py-2.5 transition">
                <svg wire:loading wire:target="analyse" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="analyse">Start medicatiereview</span>
                <span wire:loading wire:target="analyse">Analyseren, dit duurt 30-60 seconden...</span>
            </button>
        </div>
    </div>
</div>
