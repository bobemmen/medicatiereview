<div class="flex-1 flex items-center justify-center p-6" wire:init="runAnalysis">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-10 text-center">
        <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-[#E2E8F0] border-t-[#1A4F82] mb-6"></div>
        <h2 class="text-lg font-semibold text-[#0F172A] mb-3">Claude analyseert het dossier</h2>

        <div class="relative h-10 overflow-hidden mb-4" aria-live="polite">
            <p id="analyzing-step"
               class="text-sm text-[#1A4F82] font-medium transition-all duration-500"
               style="opacity:1; transform: translateY(0);">
                Patiëntgegevens inlezen...
            </p>
        </div>

        <div class="w-full bg-[#E2E8F0] rounded-full h-1 mb-4 overflow-hidden">
            <div id="analyzing-bar"
                 class="h-1 rounded-full bg-[#1A4F82] transition-all duration-[1200ms] ease-in-out"
                 style="width: 4%;">
            </div>
        </div>

        <p class="text-xs text-[#94A3B8]">Dit duurt meestal 30–60 seconden</p>
    </div>
</div>

<script>
(function () {
    var steps = [
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
    ];

    var el = document.getElementById('analyzing-step');
    var bar = document.getElementById('analyzing-bar');
    var i = 0;

    function next() {
        if (!el || !bar || i >= steps.length) return;
        var s = steps[i++];

        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';

        setTimeout(function () {
            el.textContent = s.text;
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 300);

        bar.style.width = s.pct + '%';

        var delay = i < 4 ? 2800 : i < 9 ? 3800 : 4500;
        setTimeout(next, delay);
    }

    next();
})();
</script>
