<div class="flex-1 flex items-center justify-center p-6" wire:init="runSummarize">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-10 text-center"
         x-data="{
            steps: [
                { pct: 10, text: 'Dossier inlezen…' },
                { pct: 25, text: 'Klinisch relevante gegevens identificeren…' },
                { pct: 40, text: 'Voorgeschiedenis en episodes extraheren…' },
                { pct: 55, text: 'Actieve medicatielijst structureren…' },
                { pct: 70, text: 'Recente labwaarden selecteren…' },
                { pct: 85, text: 'Samenvatting opbouwen…' },
                { pct: 94, text: 'Afronden…' },
            ],
            i: 0,
            visible: true,
            get pct()  { return this.steps[this.i]?.pct  ?? 94 },
            get text() { return this.steps[this.i]?.text ?? '' },
            advance() {
                if (this.i >= this.steps.length - 1) return;
                this.visible = false;
                setTimeout(() => {
                    this.i++;
                    this.visible = true;
                    setTimeout(() => this.advance(), 3500);
                }, 350);
            }
         }"
         x-init="setTimeout(() => advance(), 2500)">

        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-[#E2E8F0] border-t-[#1A4F82] mb-6"></div>
        <h2 class="text-lg font-semibold text-[#0F172A] mb-2">Claude maakt een samenvatting</h2>
        <p class="text-xs text-[#64748B] mb-5">
            Het dossier is groter dan {{ number_format(\App\Livewire\MedicationReview::TOKEN_THRESHOLD, 0, ',', '.') }} tokens ({{ number_format($estimatedTokens, 0, ',', '.') }} geschat).<br/>
            We extraheren eerst de voor een medicatiereview relevante gegevens zodat je kunt verifiëren wat er wordt doorgestuurd.
        </p>

        <div class="relative h-10 overflow-hidden mb-4">
            <p class="text-sm text-[#1A4F82] font-medium transition-all duration-300"
               :style="visible ? 'opacity:1;transform:translateY(0)' : 'opacity:0;transform:translateY(-8px)'"
               x-text="text"
               aria-live="polite">
            </p>
        </div>

        <div class="w-full bg-[#E2E8F0] rounded-full h-1 mb-4 overflow-hidden">
            <div class="h-1 rounded-full bg-[#1A4F82] transition-all duration-[1200ms] ease-in-out"
                 :style="'width:' + pct + '%'">
            </div>
        </div>

        <p class="text-xs text-[#94A3B8]">Dit duurt meestal 20–40 seconden</p>
    </div>
</div>
