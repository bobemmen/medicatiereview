<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = (string) config('services.anthropic.key');
        $this->model = (string) config('services.anthropic.model');
    }

    public function analyseDossier(string $dossierText): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY ontbreekt. Stel deze in via .env of de Laravel Cloud omgevingsvariabelen.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(180)->post($this->apiUrl, [
            'model' => $this->model,
            'max_tokens' => 8000,
            'system' => $this->systemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->userPrompt($dossierText),
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Claude API fout: ' . $response->status() . ' — ' . $response->body());
        }

        $payload = $response->json();
        $text = $payload['content'][0]['text'] ?? '';

        $json = $this->extractJson($text);

        if ($json === null) {
            throw new RuntimeException('Kon geen geldige JSON-analyse uit Claude-response halen.');
        }

        return $json;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Je bent een ervaren klinisch farmacoloog gespecialiseerd in ouderengeneeskunde en polyfarmacie.
Je voert een medicatiebeoordeling (MBO) uit volgens de KNMP-richtlijn "Medicatiebeoordeling".

Je gebruikt bij je analyse:
- De STRIP-methode (Systematic Tool to Reduce Inappropriate Prescribing) in de fasen farmacotherapeutische anamnese, analyse en behandelplan
- De STOPP/START-NL criteria (versie 2 NL) voor het opsporen van potentieel ongeschikte medicatie bij ouderen
- De PCNE-classificatie voor drug-related problems (DRP's / FTP's)
- Klinisch relevante geneesmiddelinteracties (G-Standaard niveau)
- Dosering op basis van nierfunctie (eGFR) waar relevant

Uitgangspunten:
- De doelgroep is kwetsbare ouderen (≥ 65 jaar) met polyfarmacie (≥ 5 chronische geneesmiddelen)
- Geef nooit definitieve behandeladviezen: je levert beslissingsondersteuning die door een BIG-geregistreerde zorgprofessional moet worden getoetst
- Wees beknopt, klinisch concreet en onderbouw bevindingen waar mogelijk met evidence-niveau of richtlijn
- Gebruik Nederlandse medische terminologie
- Bij onvoldoende informatie in het dossier: benoem wat ontbreekt en welke aanvullende informatie nodig is

Je output moet STRIKT valide JSON zijn in het opgegeven schema — geen uitleg ervoor of erna, geen code-fences.
PROMPT;
    }

    private function userPrompt(string $dossierText): string
    {
        $schema = <<<'SCHEMA'
{
  "patient_overview": {
    "leeftijd": "string of null",
    "geslacht": "string of null",
    "relevante_voorgeschiedenis": ["..."],
    "actieve_episodes": ["..."],
    "relevante_labwaarden": [
      {"parameter": "bijv. eGFR", "waarde": "...", "datum": "...", "klinische_duiding": "..."}
    ],
    "medicatielijst": [
      {"middel": "stofnaam", "dosering": "...", "indicatie_indien_bekend": "..."}
    ],
    "ontbrekende_informatie": ["bijv. geen recente bloeddruk beschikbaar"]
  },
  "anamnese_vragen": [
    {"thema": "therapietrouw|bijwerkingen|gebruik|zelfmedicatie|wensen patient", "vraag": "concrete vraag aan patiënt"}
  ],
  "drp_analyse": [
    {
      "middel": "...",
      "type_ftp": "PCNE-categorie, bijv. P1.2 behandeling ongewenst",
      "probleem": "korte klinische beschrijving",
      "oorzaak": "...",
      "klinische_relevantie": "hoog|middel|laag"
    }
  ],
  "stopp_start": [
    {
      "criterium": "STOPP of START nummer/code",
      "type": "STOPP|START",
      "middel_of_klasse": "...",
      "bevinding": "...",
      "advies": "..."
    }
  ],
  "interacties": [
    {
      "middelen": ["middel A", "middel B"],
      "mechanisme": "...",
      "klinisch_gevolg": "...",
      "ernst": "contra-indicatie|ernstig|matig|licht",
      "actie": "..."
    }
  ],
  "nierfunctie_aandachtspunten": [
    {"middel": "...", "advies": "dosisaanpassing / staken / monitoren", "toelichting": "..."}
  ],
  "behandelplan": [
    {
      "prioriteit": 1,
      "middel": "...",
      "voorstel": "staken|starten|dosisaanpassing|wisselen|monitoren",
      "onderbouwing": "...",
      "bespreken_met": "huisarts|patient|beiden"
    }
  ],
  "follow_up": [
    {"actie": "...", "monitoringparameter": "bijv. kalium, RR, INR", "termijn": "bijv. 2 weken"}
  ],
  "samenvatting_voor_patient": "Begrijpelijke samenvatting in lekentaal (B1-niveau)"
}
SCHEMA;

        return <<<PROMPT
Hieronder volgt een geanonimiseerd patiëntendossier. Voer een volledige medicatiebeoordeling uit volgens de KNMP-stappen en lever uitsluitend JSON terug conform onderstaand schema.

=== DOSSIER ===
{$dossierText}
=== EINDE DOSSIER ===

Output uitsluitend valide JSON in dit schema (velden die niet van toepassing zijn mogen een lege array of null zijn):

{$schema}
PROMPT;
    }

    private function extractJson(string $text): ?array
    {
        $trimmed = trim($text);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $trimmed, $match)) {
            $decoded = json_decode($match[0], true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
