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

    public const DRP_TYPES = [
        'Indicatieprobleem',
        'Doseringsafwijking',
        'Bijwerking (vermoed)',
        'Interactie',
        'Adherentieprobleem',
        'Onnodig geneesmiddel',
    ];

    public string $step = 'disclaimer'; // disclaimer | input | analyzing | review | rapport | error
    public string $dossierText = '';
    public $upload = null;
    public ?array $analysis = null;
    public ?string $errorMessage = null;

    /** Per-medicatie state tijdens review */
    public array $checked = [];
    public array $drps = [];
    public array $notes = [];

    /** UI state tijdens review */
    public string $filter = 'alle';
    public string $search = '';
    public ?int $expandedMed = null;

    public function acceptDisclaimer(): void
    {
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
Initialen: J.d.V.
Geboortedatum: 12-03-1950 (74 jaar)
Geslacht: man
Gewicht: 82 kg
Huisarts: Dr. A. Vermeer
Allergieën: Penicilline

Voorgeschiedenis:
- Diabetes mellitus type 2 (sinds 2012)
- Hypertensie
- Hypercholesterolemie
- Hartfalen (NYHA II)
- Boezemfibrilleren
- Chronische nierinsufficiëntie (G3a)

Actuele episodes:
- Vermoeidheid
- Maagklachten

Recente labwaarden (2 weken geleden):
- eGFR: 54 ml/min (G3a)
- Creatinine: 112 µmol/L
- HbA1c: 54 mmol/mol
- INR: 1.8
- Kalium: 4.3 mmol/L

Actuele medicatie:
- Metformine 500 mg 2×/dag (Diabetes mellitus type 2)
- Lisinopril 10 mg 1×/dag (Hypertensie)
- Simvastatine 40 mg 1×/dag (Hypercholesterolemie)
- Omeprazol 20 mg 1×/dag (Maagprotectie, al sinds 2019)
- Carvedilol 12,5 mg 2×/dag (Hartfalen)
- Furosemide 40 mg 1×/dag (Vochtretentie)
- Acenocoumarol volgens trombosedienst (Boezemfibrilleren)
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

        // Only set the step — the analyzing screen calls runAnalysis() via wire:init
        // so the browser first renders the loading screen before the API call starts.
        $this->step = 'analyzing';
    }

    public function runAnalysis(): void
    {
        if ($this->step !== 'analyzing') {
            return;
        }

        try {
            $claude = app(ClaudeService::class);
            $this->analysis = $claude->analyseDossier($this->dossierText);
            $this->initialiseReviewState();
            $this->step = 'review';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->step = 'error';
        }
    }

    private function initialiseReviewState(): void
    {
        $this->checked = [];
        $this->drps = [];
        $this->notes = [];
        $this->filter = 'alle';
        $this->search = '';
        $this->expandedMed = null;

        foreach ($this->analysis['medicatie'] ?? [] as $index => $med) {
            $this->checked[$index] = false;
            $this->notes[$index] = '';
            $this->drps[$index] = [];

            foreach ($med['drp_typen'] ?? [] as $type) {
                $this->drps[$index][$type] = true;
            }
        }
    }

    public function toggleChecked(int $id): void
    {
        $this->checked[$id] = !($this->checked[$id] ?? false);
    }

    public function toggleDrp(int $id, string $type): void
    {
        $this->drps[$id] = $this->drps[$id] ?? [];
        $this->drps[$id][$type] = !($this->drps[$id][$type] ?? false);
    }

    public function toggleExpanded(int $id): void
    {
        $this->expandedMed = $this->expandedMed === $id ? null : $id;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function markAsDiscussed(int $id): void
    {
        if (!($this->checked[$id] ?? false)) {
            $this->checked[$id] = true;
        }
        $this->expandedMed = null;
    }

    public function goToRapport(): void
    {
        $this->step = 'rapport';
    }

    public function backToReview(): void
    {
        $this->step = 'review';
    }

    public function startOver(): void
    {
        $this->dossierText = '';
        $this->analysis = null;
        $this->errorMessage = null;
        $this->checked = [];
        $this->drps = [];
        $this->notes = [];
        $this->filter = 'alle';
        $this->search = '';
        $this->expandedMed = null;
        $this->step = 'input';
    }

    public function getFilteredMedsProperty(): array
    {
        $meds = $this->analysis['medicatie'] ?? [];
        $filter = $this->filter;
        $search = strtolower(trim($this->search));

        $result = [];
        foreach ($meds as $index => $med) {
            if ($filter !== 'alle' && ($med['status'] ?? '') !== $filter) {
                continue;
            }
            if ($search !== '' && !str_contains(strtolower($med['naam'] ?? ''), $search)) {
                continue;
            }
            $result[$index] = $med;
        }

        return $result;
    }

    public function getStatusCountsProperty(): array
    {
        $meds = $this->analysis['medicatie'] ?? [];
        $counts = ['alle' => count($meds), 'ok' => 0, 'aandacht' => 0, 'drp' => 0];

        foreach ($meds as $med) {
            $status = $med['status'] ?? 'ok';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function getDoneCountProperty(): int
    {
        return count(array_filter($this->checked));
    }

    public function downloadPdf()
    {
        if (!$this->analysis) {
            return null;
        }

        $pdf = Pdf::loadView('pdf.medication-review', [
            'analysis' => $this->analysis,
            'checked' => $this->checked,
            'drps' => $this->drps,
            'notes' => $this->notes,
            'apotheek' => $this->apotheek(),
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'medicatiereview-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function apotheek(): array
    {
        return [
            'naam' => config('services.apotheek.naam'),
            'adres' => config('services.apotheek.adres'),
            'telefoon' => config('services.apotheek.telefoon'),
            'apotheker' => config('services.apotheek.apotheker'),
        ];
    }

    public function render()
    {
        return view('livewire.medication-review', [
            'drpTypes' => self::DRP_TYPES,
            'apotheek' => $this->apotheek(),
        ]);
    }
}
