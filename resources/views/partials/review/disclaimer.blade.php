<div class="flex-1 flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-sm border border-[#E2E8F0] p-8">

        <div class="flex flex-col items-center mb-6 text-center">
            <x-logo :height="36" class="mb-4" />
            <h1 class="text-xl font-semibold text-[#0F172A] mt-4">Voordat je begint</h1>
            <p class="text-sm text-[#64748B] mt-0.5">Lees deze informatie zorgvuldig door.</p>
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
