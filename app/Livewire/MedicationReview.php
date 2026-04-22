<?php

namespace App\Livewire;

use App\Services\ClaudeService;
use App\Services\DossierExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class MedicationReview extends Component
{
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

    public function downloadPdf()
    {
        if (!$this->analysis) {
            return null;
        }

        $pdf = Pdf::loadView('pdf.medication-review', ['analysis' => $this->analysis])
            ->setPaper('a4');

        $filename = 'medicatiebeoordeling-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.medication-review');
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
}
