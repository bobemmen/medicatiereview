<?php

use App\Services\ClaudeService;
use App\Services\DossierExtractor;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public bool $disclaimerAccepted = false;
    public string $step = 'disclaimer';
    public string $dossierText = '';
    public $upload = null;
    public ?array $analysis = null;
    public ?string $errorMessage = null;

    public function acceptDisclaimer(): void
    {
        $this->disclaimerAccepted = true;
        $this->step = 'input';
    }

    public function updatedUpload(): void
    {
        $this->errorMessage = null;

        if (!$this->upload) {
            return;
        }

        try {
            $extractor = app(DossierExtractor::class);
            $this->dossierText = $extractor->extractFromUpload($this->upload);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Kon bestand niet uitlezen: ' . $e->getMessage();
        } finally {
            $this->upload = null;
        }
    }

    public function loadExample(): void
    {
        $this->errorMessage = null;
        $this->dossierText = <<<'TXT'
Patiëntgegevens (geanonimiseerd)
Leeftijd: 82 jaar
Geslacht: vrouw
Woonsituatie: zelfstandig, mantelzorg door dochter

Voorgeschiedenis:
- Hypertensie (sinds 2008)
- Atriumfibrilleren (sinds 2015)
- Diabetes mellitus type 2 (sinds 2010)
- Osteoporose met eerdere wervelfractuur (2019)
- Chronische nierinsufficiëntie (eGFR 42 ml/min)
- Milde cognitieve achteruitgang (2022)
- Maagklachten, refluxklachten
- Val 3 maanden geleden (geen fractuur)

Actuele episodes:
- Duizeligheid bij opstaan (sinds 6 weken)
- Slaapproblemen
- Obstipatie

Recente labwaarden (2 weken geleden):
- eGFR: 42 ml/min
- Kalium: 5.2 mmol/L (licht verhoogd)
- Natrium: 136 mmol/L
- HbA1c: 52 mmol/mol
- INR: 2.8
- Hb: 7.2 mmol/L

Huidige medicatie:
- Acenocoumarol volgens trombosedienst
- Metoprolol 50 mg 1dd
- Enalapril 20 mg 1dd
- Hydrochloorthiazide 25 mg 1dd
- Metformine 850 mg 2dd
- Gliclazide 80 mg 1dd
- Omeprazol 20 mg 1dd (sinds 2014)
- Alendroninezuur 70 mg 1x per week
- Calciumcarbonaat/colecalciferol 500/800 1dd
- Oxazepam 10 mg zn bij slaapproblemen (gebruikt dagelijks volgens dochter)
- Amitriptyline 25 mg ante noctem (sinds val, tegen slaap)
- Ibuprofen 400 mg zn bij pijn (gebruikt regelmatig volgens dochter)

Bijzonderheden:
- Dochter meldt dat patiënt soms vergeet medicatie in te nemen
- Patiënt klaagt over droge mond en moeite met plassen
TXT;
    }

    public function analyse(): void
    {
        $this->errorMessage = null;

        if (trim($this->dossierText) === '') {
            $this->errorMessage = 'Plak of upload eerst een geanonimiseerd dossier.';
            return;
        }

        if (mb_strlen($this->dossierText) > 50000) {
            $this->errorMessage = 'Dossier is te lang (max 50.000 tekens).';
            return;
        }

        $this->step = 'analyzing';

        try {
            $claude = app(ClaudeService::class);
            $this->analysis = $claude->analyseDossier($this->dossierText);
            $this->step = 'results';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->step = 'error';
        }
    }

    public function startOver(): void
    {
        $this->dossierText = '';
        $this->analysis = null;
        $this->errorMessage = null;
        $this->step = 'input';
    }

    public function downloadMarkdown()
    {
        if (!$this->analysis) {
            return null;
        }

        $markdown = $this->buildMarkdown($this->analysis);
        $filename = 'medicatiebeoordeling-' . now()->format('Ymd-His') . '.md';

        return response()->streamDownload(
            fn() => print($markdown),
            $filename,
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }

    private function buildMarkdown(array $a): string
    {
        $md = "# Medicatiebeoordeling\n\n";
        $md .= "*Gegenereerd op " . now()->format('d-m-Y H:i') . " — AI-ondersteuning, te toetsen door zorgprofessional*\n\n---\n\n";

        $p = $a['patient_overview'] ?? [];
        $md .= "## 1. Patiëntoverzicht\n\n";
        $md .= "- **Leeftijd:** " . ($p['leeftijd'] ?? '—') . "\n";
        $md .= "- **Geslacht:** " . ($p['geslacht'] ?? '—') . "\n\n";

        $md .= "### Voorgeschiedenis\n";
        foreach ($p['relevante_voorgeschiedenis'] ?? [] as $item) {
            $md .= "- $item\n";
        }

        $md .= "\n### Actieve episodes\n";
        foreach ($p['actieve_episodes'] ?? [] as $item) {
            $md .= "- $item\n";
        }

        $md .= "\n### Labwaarden\n";
        foreach ($p['relevante_labwaarden'] ?? [] as $lab) {
            $md .= "- **{$lab['parameter']}**: {$lab['waarde']} ({$lab['datum']}) — {$lab['klinische_duiding']}\n";
        }

        $md .= "\n### Medicatielijst\n";
        foreach ($p['medicatielijst'] ?? [] as $m) {
            $md .= "- **{$m['middel']}** — {$m['dosering']} ({$m['indicatie_indien_bekend']})\n";
        }

        if (!empty($p['ontbrekende_informatie'])) {
            $md .= "\n### Ontbrekende informatie\n";
            foreach ($p['ontbrekende_informatie'] as $item) {
                $md .= "- $item\n";
            }
        }

        $md .= "\n## 2. Anamnese-vragen (STRIP stap 1)\n\n";
        foreach ($a['anamnese_vragen'] ?? [] as $v) {
            $md .= "- **[{$v['thema']}]** {$v['vraag']}\n";
        }

        $md .= "\n## 3. Farmacotherapeutische analyse\n\n### Drug-related problems (PCNE)\n\n";
        foreach ($a['drp_analyse'] ?? [] as $d) {
            $md .= "- **{$d['middel']}** — {$d['type_ftp']} ({$d['klinische_relevantie']})\n  - {$d['probleem']}\n  - Oorzaak: {$d['oorzaak']}\n";
        }

        $md .= "\n### STOPP/START-NL\n";
        foreach ($a['stopp_start'] ?? [] as $s) {
            $md .= "- **[{$s['type']} {$s['criterium']}]** {$s['middel_of_klasse']}\n  - {$s['bevinding']}\n  - Advies: {$s['advies']}\n";
        }

        $md .= "\n### Interacties\n";
        foreach ($a['interacties'] ?? [] as $i) {
            $mid = implode(' + ', $i['middelen'] ?? []);
            $md .= "- **$mid** ({$i['ernst']})\n  - Mechanisme: {$i['mechanisme']}\n  - Gevolg: {$i['klinisch_gevolg']}\n  - Actie: {$i['actie']}\n";
        }

        if (!empty($a['nierfunctie_aandachtspunten'])) {
            $md .= "\n### Nierfunctie\n";
            foreach ($a['nierfunctie_aandachtspunten'] as $n) {
                $md .= "- **{$n['middel']}** — {$n['advies']}: {$n['toelichting']}\n";
            }
        }

        $md .= "\n## 4. Behandelplan\n\n";
        foreach ($a['behandelplan'] ?? [] as $b) {
            $md .= "### Prioriteit {$b['prioriteit']}: {$b['middel']} — {$b['voorstel']}\n";
            $md .= "{$b['onderbouwing']}\n\n*Bespreken met: {$b['bespreken_met']}*\n\n";
        }

        $md .= "## 5. Follow-up en monitoring\n\n";
        foreach ($a['follow_up'] ?? [] as $f) {
            $md .= "- **{$f['actie']}** — {$f['monitoringparameter']} ({$f['termijn']})\n";
        }

        if (!empty($a['samenvatting_voor_patient'])) {
            $md .= "\n## 6. Samenvatting voor de patiënt\n\n";
            $md .= $a['samenvatting_voor_patient'] . "\n";
        }

        $md .= "\n---\n\n*Dit is beslissingsondersteuning. De BIG-geregistreerde apotheker/arts blijft verantwoordelijk voor het definitieve oordeel en behandelbeleid.*\n";

        return $md;
    }
}; ?>

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
                <button wire:click="acceptDisclaimer"
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

                <button wire:click="loadExample" type="button"
                    class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 p-4 text-left transition">
                    <span class="text-sm font-medium text-slate-700">Gebruik demo-dossier</span>
                    <span class="block text-xs text-slate-500 mt-1">Vrouwelijke patiënt 82 jaar met polyfarmacie — geschikt voor demo</span>
                </button>
            </div>

            <label class="block text-sm font-medium text-slate-700 mb-2">Dossiertekst</label>
            <textarea wire:model.live.debounce.500ms="dossierText" rows="14"
                placeholder="Plak hier het geanonimiseerde dossier (voorgeschiedenis, episodes, lab, medicatie)..."
                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono text-slate-800 focus:border-slate-500 focus:ring-1 focus:ring-slate-500"></textarea>

            <div class="mt-2 text-xs text-slate-400">{{ strlen($dossierText) }} tekens</div>

            @if ($errorMessage)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button wire:click="analyse"
                    @disabled(trim($dossierText) === '')
                    class="rounded-lg bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 text-white font-medium px-5 py-2.5 text-sm transition">
                    Start medicatiebeoordeling
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
            <button wire:click="startOver"
                class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 text-sm transition">
                Opnieuw proberen
            </button>
        </div>
    @endif

    @if ($step === 'results' && $analysis)
        @include('partials.results', ['analysis' => $analysis])

        <div class="flex flex-wrap gap-3 justify-between items-center">
            <button wire:click="startOver"
                class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 text-sm transition">
                Nieuwe beoordeling
            </button>
            <button wire:click="downloadMarkdown"
                class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium px-4 py-2 text-sm transition">
                Download Markdown-verslag
            </button>
        </div>
    @endif

</div>
