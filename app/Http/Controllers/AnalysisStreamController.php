<?php

namespace App\Http\Controllers;

use App\Services\ClaudeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalysisStreamController extends Controller
{
    /**
     * Streaming-endpoint dat Anthropic's SSE doorpompt naar de browser.
     *
     * Doel: de Laravel Cloud proxy heeft een cap van 60s op idle-verbindingen.
     * Door elke token-chunk van Anthropic direct als SSE-heartbeat te flushen,
     * staat de verbinding nooit langer dan enkele ms stil en raken we de cap
     * niet. Geen queue, geen persistentie — het dossier staat alleen in
     * PHP-geheugen tijdens de request en verdwijnt daarna.
     */
    public function stream(Request $request, ClaudeService $claude): StreamedResponse
    {
        $validated = $request->validate([
            'dossier' => ['required', 'string', 'max:50000'],
        ]);

        if (!$this->hasReviewsAvailable()) {
            return response()->stream(function () {
                $this->sseEmit('error', [
                    'message' => 'Je hebt het dagelijkse demo-limiet bereikt. Ontgrendel via de demo-badge of probeer het morgen opnieuw.',
                ]);
            }, 200, $this->sseHeaders());
        }

        return response()->stream(function () use ($claude, $validated) {
            // Disable alle output-buffering zodat elke flush direct de TCP-socket bereikt.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            // default_socket_timeout staat standaard op 60s — te kort voor grote dossiers.
            ini_set('default_socket_timeout', '300');
            ignore_user_abort(false);

            $this->sseEmit('start', ['ts' => microtime(true)]);

            try {
                foreach ($claude->streamAnalyseDossier($validated['dossier']) as [$event, $data]) {
                    if ($event === 'heartbeat') {
                        $this->sseEmit('heartbeat', ['ts' => microtime(true)]);
                    } elseif ($event === 'result') {
                        $this->incrementReviewCount();
                        $this->sseEmit('result', $data);
                    }

                    if (connection_aborted()) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Analyse-stream faalde', ['error' => $e->getMessage()]);
                $this->sseEmit('error', ['message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, no-transform',
            'Connection' => 'keep-alive',
            // Schakelt nginx/proxy-buffering uit — essentieel voor tijdige flushes
            'X-Accel-Buffering' => 'no',
        ];
    }

    private function sseEmit(string $event, mixed $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        @ob_flush();
        @flush();
    }

    // Rate-limit helpers: bewust gedupliceerd uit MedicationReview zodat de
    // controller zelfstandig kan valideren (ook bij directe curl-calls).

    private function dailyLimitKey(): string
    {
        return 'mbo_reviews:' . now()->format('Y-m-d') . ':' . request()->ip();
    }

    private function reviewsUsedToday(): int
    {
        return (int) Cache::get($this->dailyLimitKey(), 0);
    }

    private function isUnlocked(): bool
    {
        return (bool) Session::get('demo_unlocked', false);
    }

    private function hasReviewsAvailable(): bool
    {
        if ($this->isUnlocked()) {
            return true;
        }
        $limit = (int) config('services.demo.daily_limit', 10);
        return $this->reviewsUsedToday() < $limit;
    }

    private function incrementReviewCount(): void
    {
        if ($this->isUnlocked()) {
            return;
        }
        Cache::put($this->dailyLimitKey(), $this->reviewsUsedToday() + 1, now()->endOfDay());
    }
}
