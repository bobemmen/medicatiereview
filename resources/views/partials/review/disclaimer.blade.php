<div class="flex-1 flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-8">

        <div class="flex items-start gap-3 mb-5">
            <div class="w-10 h-10 rounded-lg bg-[#EBF3FC] flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"
                        stroke="#1A4F82" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-semibold text-[#0F172A]">Voordat je begint</h1>
                <p class="text-sm text-[#64748B] mt-0.5">Lees deze informatie zorgvuldig door.</p>
            </div>
        </div>

        <div class="space-y-4 text-sm text-[#334155] leading-relaxed">
            <p>Deze applicatie biedt <strong>beslissingsondersteuning</strong> voor een medicatiebeoordeling (MBO) op basis van de KNMP-richtlijn. De analyse wordt uitgevoerd door Claude (AI) en is uitsluitend bedoeld voor <strong>BIG-geregistreerde zorgprofessionals</strong>.</p>

            <ul class="list-disc pl-5 space-y-2">
                <li>Gebruik <strong>uitsluitend geanonimiseerde</strong> dossierinformatie. Verwijder namen, BSN, geboortedatum en andere direct identificerende gegevens.</li>
                <li>De tool vervangt niet het klinisch oordeel. Alle aanbevelingen moeten door een bevoegde zorgprofessional worden getoetst.</li>
                <li>Dossiergegevens worden <strong>niet opgeslagen</strong>. Na het afsluiten van de sessie is alle data gewist.</li>
                <li>Voor de analyse wordt de inhoud verstuurd naar de Anthropic API (Claude).</li>
            </ul>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="button" wire:click="acceptDisclaimer"
                class="px-5 py-2.5 bg-[#1A4F82] hover:bg-[#1D5FA0] text-white text-sm font-medium rounded-[5px] transition">
                Ik begrijp het — start
            </button>
        </div>
    </div>
</div>
