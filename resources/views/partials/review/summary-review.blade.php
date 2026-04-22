<div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-4xl mx-auto">

        <div class="bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-8">

            <div class="flex items-start gap-3 mb-5">
                <div class="w-9 h-9 rounded-full bg-[#E6EFFA] flex items-center justify-center shrink-0 mt-0.5">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 7a1 1 0 011 1v4a1 1 0 11-2 0v-4a1 1 0 011-1zm0-3a1 1 0 110 2 1 1 0 010-2z" fill="#1A4F82"/></svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-[#0F172A] mb-1">Controleer de samenvatting</h2>
                    <p class="text-sm text-[#475569] leading-relaxed">
                        Het originele dossier is te groot om in één keer te analyseren ({{ number_format($estimatedTokens, 0, ',', '.') }} tokens). Claude heeft hieronder de voor een medicatiereview relevante gegevens geëxtraheerd. Controleer of alles klopt en compleet genoeg is — je kunt hieronder bewerken, aanvullen of verwijderen voordat de analyse start.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-sm text-red-700">
                    {{ $errorMessage }}
                </div>
            @endif

            <label class="block text-xs font-medium text-[#64748B] uppercase tracking-wide mb-2">
                Samenvatting (bewerkbaar)
            </label>
            <textarea wire:model.live.debounce.400ms="dossierSummary"
                      rows="24"
                      class="w-full rounded-md border border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3 text-sm text-[#0F172A] font-mono leading-relaxed focus:outline-none focus:border-[#1A4F82] focus:ring-1 focus:ring-[#1A4F82] resize-y"
                      placeholder="Samenvatting wordt hier getoond…"></textarea>

            <div class="mt-2 text-xs text-[#94A3B8] flex justify-between">
                <span>{{ mb_strlen($dossierSummary) }} tekens · ~{{ number_format((int) ceil(mb_strlen($dossierSummary) / 3.5), 0, ',', '.') }} tokens</span>
                <span>Bewerkingen worden automatisch opgeslagen</span>
            </div>

            <div class="flex items-center justify-between gap-3 mt-7 pt-5 border-t border-[#E2E8F0]">
                <button type="button" wire:click="startOver"
                        class="text-sm text-[#64748B] hover:text-[#0F172A] transition">
                    Annuleren
                </button>

                <div class="flex items-center gap-2.5">
                    <button type="button" wire:click="regenerateSummary"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-md border border-[#CBD5E1] bg-white text-sm text-[#0F172A] font-medium hover:bg-[#F1F5F9] transition inline-flex items-center gap-1.5">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M11.5 7a4.5 4.5 0 11-1.32-3.18M12 2v3h-3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Opnieuw samenvatten
                    </button>
                    <button type="button" wire:click="confirmSummary"
                            wire:loading.attr="disabled"
                            class="px-5 py-2 rounded-md bg-[#1A4F82] text-white text-sm font-medium hover:bg-[#143E67] transition inline-flex items-center gap-1.5">
                        Doorgaan met analyse
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
