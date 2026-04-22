<div class="flex-1 flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-sm border border-red-200 p-6">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-[#FEF2F2] flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"
                        stroke="#DC2626" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-[#991B1B]">Er ging iets mis</h2>
        </div>
        <p class="text-sm text-[#334155] mb-5 break-words">{{ $errorMessage }}</p>
        <button type="button" wire:click="startOver"
            class="px-4 py-2 rounded-[5px] border border-[#E2E8F0] bg-white hover:bg-[#F1F5F9] text-[#334155] text-sm font-medium transition">
            Opnieuw proberen
        </button>
    </div>
</div>
