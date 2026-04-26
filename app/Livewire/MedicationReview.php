<?php

namespace App\Livewire;

use App\Demo\MockAnalysis;
use App\Services\ClaudeService;
use App\Services\DossierExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
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

    public string $step = 'disclaimer'; // disclaimer | input | summarizing | summary_review | analyzing | review | rapport | error
    public string $dossierText = '';
    public string $dossierSummary = '';
    public int $estimatedTokens = 0;
    public const TOKEN_THRESHOLD = 5000;
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
    public ?string $sortField = null;       // 'naam' | 'status' | null
    public string $sortDirection = 'asc';   // 'asc' | 'desc'

    /** Anamnesevragen — extra 10 worden on-demand gegenereerd */
    public array $extraAnamneseVragen = [];
    public bool $loadingExtraVragen = false;

    /** Demo / rate-limit state */
    public bool $showUnlockModal = false;
    public string $unlockInput = '';
    public ?string $unlockError = null;

    /**
     * Demo-modus: mount met `?demo=review` of `?demo=rapport` (via /demo/{demo})
     * laadt mock-data en springt direct naar het review- of rapport-scherm.
     * Bedoeld voor snelle UI-iteratie zonder de hele analyse-pipeline te draaien.
     */
    public function mount(?string $demo = null): void
    {
        if ($demo === null) {
            return;
        }

        if (!in_array($demo, ['review', 'rapport'], true)) {
            return;
        }

        $this->analysis = MockAnalysis::data();
        $this->initialiseReviewState();

        // Demonstreer beide noot-states: notes[0] (Diltiazem) is door de
        // apotheker gewijzigd t.o.v. de AI-versie -> potlood-icoon. Pantoprazol
        // (index 5) is afgevinkt zodat de niet-greyed "done"-stijl zichtbaar is.
        $this->notes[0] = 'Met cardioloog overlegd op 24-04: vandaag staken, amlodipine 5 mg starten. Patiënte volgende week bellen.';
        $this->checked[5] = true;

        $this->step = $demo;
    }

    public function acceptDisclaimer(): void
    {
        $this->step = 'input';
    }

    // --------------------------------------------------------------------
    //  Demo rate-limit & unlock
    // --------------------------------------------------------------------

    private function dailyLimitKey(): string
    {
        return 'mbo_reviews:' . now()->format('Y-m-d') . ':' . request()->ip();
    }

    public function getReviewsUsedTodayProperty(): int
    {
        return (int) Cache::get($this->dailyLimitKey(), 0);
    }

    public function getDailyLimitProperty(): int
    {
        return (int) config('services.demo.daily_limit', 10);
    }

    public function getIsUnlockedProperty(): bool
    {
        return (bool) Session::get('demo_unlocked', false);
    }

    private function incrementReviewCount(): void
    {
        if ($this->isUnlocked) {
            return;
        }
        $key = $this->dailyLimitKey();
        Cache::put($key, $this->reviewsUsedToday + 1, now()->endOfDay());
    }

    private function hasReviewsAvailable(): bool
    {
        return $this->isUnlocked || $this->reviewsUsedToday < $this->dailyLimit;
    }

    public function openUnlock(): void
    {
        $this->showUnlockModal = true;
        $this->unlockInput = '';
        $this->unlockError = null;
    }

    public function closeUnlock(): void
    {
        $this->showUnlockModal = false;
        $this->unlockInput = '';
        $this->unlockError = null;
    }

    public function submitUnlock(): void
    {
        $expected = (string) config('services.demo.unlock_code');
        if ($expected !== '' && hash_equals($expected, $this->unlockInput)) {
            Session::put('demo_unlocked', true);
            $this->showUnlockModal = false;
            $this->unlockInput = '';
            $this->unlockError = null;
        } else {
            $this->unlockError = 'Onjuiste code.';
        }
    }

    public function lockDemo(): void
    {
        Session::forget('demo_unlocked');
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

        if (!$this->hasReviewsAvailable()) {
            $this->errorMessage = "Je hebt het dagelijkse demo-limiet van {$this->dailyLimit} reviews bereikt. Klik op de Demo-badge rechtsboven om te ontgrendelen of probeer het morgen opnieuw.";
            return;
        }

        $this->estimatedTokens = ClaudeService::estimateTokens($this->dossierText);
        $this->step = 'summarizing';
    }

    public function runSummarize(): void
    {
        if ($this->step !== 'summarizing') {
            return;
        }

        try {
            $claude = app(ClaudeService::class);
            $this->dossierSummary = $claude->summarizeDossier($this->dossierText);
            $this->step = 'summary_review';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->step = 'error';
        }
    }

    public function confirmSummary(): void
    {
        if (trim($this->dossierSummary) === '') {
            $this->errorMessage = 'De samenvatting is leeg. Ga terug en probeer het opnieuw.';
            return;
        }

        // De gebruikergecontroleerde samenvatting wordt nu de input voor de MBO-analyse.
        $this->dossierText = $this->dossierSummary;
        $this->step = 'analyzing';
    }

    public function regenerateSummary(): void
    {
        $this->dossierSummary = '';
        $this->step = 'summarizing';
    }

    /**
     * Callback vanuit de streaming-frontend wanneer de analyse succesvol is afgerond.
     * De controller heeft de rate-limit al opgehoogd; hier alleen nog state opbouwen.
     */
    public function setAnalysisResult(array $analysis): void
    {
        if ($this->step !== 'analyzing') {
            return;
        }

        $this->analysis = $analysis;
        $this->initialiseReviewState();
        $this->step = 'review';
    }

    /**
     * Callback vanuit de streaming-frontend bij een fout tijdens de analyse.
     */
    public function setAnalysisError(string $message): void
    {
        if ($this->step !== 'analyzing') {
            return;
        }

        $this->errorMessage = $message !== '' ? $message : 'Onbekende fout tijdens de analyse.';
        $this->step = 'error';
    }

    public function loadExtraAnamneseVragen(): void
    {
        if (!empty($this->extraAnamneseVragen) || $this->loadingExtraVragen) {
            return;
        }

        $this->loadingExtraVragen = true;

        try {
            $claude  = app(ClaudeService::class);
            $patient = $this->analysis['patient'] ?? [];
            $meds    = $this->analysis['medicatie'] ?? [];

            $context = implode("\n", array_filter([
                'Patiënt: ' . ($patient['initialen_of_geanonimiseerde_naam'] ?? '')
                    . ', ' . ($patient['leeftijd'] ?? '') . ' jaar, ' . ($patient['geslacht'] ?? ''),
                'Nierfunctie: ' . ($patient['nierfunctie'] ?? ''),
                'Voorgeschiedenis: ' . implode(', ', $patient['voorgeschiedenis'] ?? []),
                'Medicatie met DRP/aandacht: ' . implode(', ', array_map(
                    fn ($m) => ($m['naam'] ?? '') . ' (' . ($m['status'] ?? '') . ')',
                    array_filter($meds, fn ($m) => ($m['status'] ?? 'ok') !== 'ok')
                )),
                'Samenvatting: ' . ($this->analysis['samenvatting'] ?? ''),
            ]));

            $this->extraAnamneseVragen = $claude->generateExtraAnamneseVragen(
                $context,
                $this->analysis['anamnese_vragen'] ?? []
            );
        } catch (\Throwable $e) {
            Log::warning('Extra anamnesevragen genereren mislukt: ' . $e->getMessage());
        } finally {
            $this->loadingExtraVragen = false;
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
        $this->sortField = null;
        $this->sortDirection = 'asc';
        $this->extraAnamneseVragen = [];
        $this->loadingExtraVragen = false;

        foreach ($this->analysis['medicatie'] ?? [] as $index => $med) {
            $this->checked[$index] = false;
            $this->notes[$index] = $med['notitie'] ?? '';
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

    public function setSort(string $field): void
    {
        if (!in_array($field, ['naam', 'status'], true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'status' ? 'desc' : 'asc';
        }
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
        $this->dossierSummary = '';
        $this->estimatedTokens = 0;
        $this->analysis = null;
        $this->errorMessage = null;
        $this->checked = [];
        $this->drps = [];
        $this->notes = [];
        $this->filter = 'alle';
        $this->search = '';
        $this->expandedMed = null;
        $this->sortField = null;
        $this->sortDirection = 'asc';
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

        if ($this->sortField !== null) {
            $dir = $this->sortDirection === 'desc' ? -1 : 1;
            $statusRank = ['drp' => 2, 'aandacht' => 1, 'ok' => 0];
            $field = $this->sortField;

            uasort($result, function (array $a, array $b) use ($dir, $statusRank, $field): int {
                if ($field === 'naam') {
                    return strcasecmp($a['naam'] ?? '', $b['naam'] ?? '') * $dir;
                }
                $rankA = $statusRank[$a['status'] ?? 'ok'] ?? 0;
                $rankB = $statusRank[$b['status'] ?? 'ok'] ?? 0;
                if ($rankA !== $rankB) {
                    return ($rankA <=> $rankB) * $dir;
                }
                $drpA = count($a['drp_typen'] ?? []);
                $drpB = count($b['drp_typen'] ?? []);
                return ($drpA <=> $drpB) * $dir;
            });
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
