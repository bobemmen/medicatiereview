<div class="space-y-6">

    @if ($step === 'disclaimer')
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-8">
            <div class="flex items-start gap-3 mb-4">
                <div class="rounded-lg bg-amber-100 p-2 text-amber-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Voordat je begint</h2>
                    <p class="text-sm text-slate-500 mt-1">Lees deze informatie zorgvuldig.</p>
                </div>
            </div>

            <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
                <p>Deze demo biedt <strong>beslissingsondersteuning</strong> voor een medicatiebeoordeling (MBO) op basis van de KNMP-richtlijn. De analyse wordt uitgevoerd door een AI (Claude) en is uitsluitend bedoeld voor <strong>BIG-geregistreerde zorgprofessionals</strong>.</p>

                <ul class="list-disc pl-5 space-y-2">
                    <li>Gebruik <strong>uitsluitend geanonimiseerde</strong> dossierinformatie. Verwijder namen, BSN, geboortedatum en andere direct identificerende gegevens.</li>
                    <li>De tool vervangt niet het klinisch oordeel. Alle aanbevelingen moeten door een bevoegde zorgprofessional worden getoetst.</li>
                    <li>Dossiergegevens worden <strong>niet opgeslagen</strong>. Na het afsluiten van de sessie is alle data gewist.</li>
                    <li>Voor de analyse wordt de inhoud verstuurd naar de Anthropic API (Claude).</li>
                </ul>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="acceptDisclaimer"
                    class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium px-5 py-2.5 text-sm transition">
                    Ik begrijp het — start
                </button>
            </div>
        </div>
    @endif

    @if ($step === 'input')
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">Dossier invoeren</h2>
            <p class="text-sm text-slate-500 mb-5">Upload een PDF/Word-bestand of plak de tekst direct in het veld.</p>

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <label class="flex flex-col justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 hover:bg-slate-100 cursor-pointer transition">
                    <span class="text-sm font-medium text-slate-700">Upload bestand</span>
                    <span class="text-xs text-slate-500 mt-1">PDF, DOCX, DOC of TXT (max 10 MB)</span>
                    <input type="file" wire:model="upload" accept=".pdf,.docx,.doc,.txt,.md" class="mt-2 text-xs text-slate-600" />
                    <div wire:loading wire:target="upload" class="text-xs text-slate-500 mt-2">Bestand uitlezen...</div>
                </label>

                <button type="button" wire:click="loadExample"
                    class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 p-4 text-left transition">
                    <span class="text-sm font-medium text-slate-700">Gebruik demo-dossier</span>
                    <span class="block text-xs text-slate-500 mt-1">Vrouwelijke patiënt 82 jaar met polyfarmacie — geschikt voor demo</span>
                </button>
            </div>

            <label class="block text-sm font-medium text-slate-700 mb-2">Dossiertekst</label>
            <textarea wire:model.blur="dossierText" rows="14"
                placeholder="Plak hier het geanonimiseerde dossier (voorgeschiedenis, episodes, lab, medicatie)..."
                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-800 focus:border-slate-500 focus:ring-1 focus:ring-slate-500"></textarea>

            @if ($errorMessage)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="analyse"
                    wire:loading.attr="disabled"
                    wire:target="analyse"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 hover:bg-slate-800 disabled:bg-slate-500 disabled:cursor-wait text-white font-medium px-5 py-2.5 text-sm transition">
                    <svg wire:loading wire:target="analyse" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="analyse">Start medicatiebeoordeling</span>
                    <span wire:loading wire:target="analyse">Analyseren, dit duurt 30-60 seconden...</span>
                </button>
            </div>
        </div>
    @endif

    @if ($step === 'analyzing')
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-10 text-center">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-slate-200 border-t-slate-800 mb-4"></div>
            <h2 class="text-lg font-semibold text-slate-900">Claude analyseert het dossier...</h2>
            <p class="text-sm text-slate-500 mt-2">Dit duurt meestal 30-90 seconden. De KNMP-stappen, STOPP/START-NL, interacties en behandelplan worden opgesteld.</p>
        </div>
    @endif

    @if ($step === 'error')
        <div class="rounded-2xl bg-white border border-red-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-red-700 mb-2">Er ging iets mis</h2>
            <p class="text-sm text-slate-700 mb-4">{{ $errorMessage }}</p>
            <button type="button" wire:click="startOver"
                class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 text-sm transition">
                Opnieuw proberen
            </button>
        </div>
    @endif

    @if ($step === 'results' && $analysis)
        @include('partials.results', ['analysis' => $analysis])

        <div class="flex flex-wrap gap-3 justify-between items-center">
            <button type="button" wire:click="startOver"
                class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 text-sm transition">
                Nieuwe beoordeling
            </button>
            <div class="flex gap-2">
                <button type="button" wire:click="downloadMarkdown"
                    class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 text-sm transition">
                    Download Markdown
                </button>
                <button type="button" wire:click="downloadPdf"
                    class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium px-4 py-2 text-sm transition">
                    Download PDF
                </button>
            </div>
        </div>
    @endif

</div>
