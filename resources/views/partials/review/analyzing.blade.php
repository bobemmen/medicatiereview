<div class="flex-1 flex items-center justify-center p-6" wire:init="runAnalysis">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-10 text-center"
         x-data="{
            steps: [
                { pct:  6, text: 'Patiëntgegevens inlezen…' },
                { pct: 13, text: 'Medicatielijst structureren…' },
                { pct: 21, text: 'ATC-codes opzoeken…' },
                { pct: 29, text: 'Indicaties en doseringen controleren…' },
                { pct: 37, text: 'STOPP-criteria toepassen…' },
                { pct: 45, text: 'START-criteria controleren…' },
                { pct: 52, text: 'Geneesmiddelinteracties analyseren…' },
                { pct: 60, text: 'Nierfunctie-aanpassingen beoordelen…' },
                { pct: 67, text: 'Drug-related problems classificeren…' },
                { pct: 74, text: 'Klinische notities formuleren…' },
                { pct: 81, text: 'Aanbevelingen opstellen…' },
                { pct: 88, text: 'Samenvatting samenstellen…' },
                { pct: 94, text: 'Analyse afronden…' },
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
                    const delay = this.i < 4 ? 2800 : this.i < 9 ? 3800 : 4500;
                    setTimeout(() => this.advance(), delay);
                }, 350);
            }
         }"
         x-init="setTimeout(() => advance(), 2800)">

        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-[#E2E8F0] border-t-[#1A4F82] mb-6"></div>
        <h2 class="text-lg font-semibold text-[#0F172A] mb-3">Claude analyseert het dossier</h2>

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

        <p class="text-xs text-[#94A3B8]">Dit duurt meestal 30–60 seconden</p>
    </div>
</div>
