<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private const TOOL_NAME = 'submit_mbo_analysis';

    // Samenvatten is een extractie-taak; Haiku is daar ruim snel/goed genoeg voor
    // en rond 3-5x sneller dan Sonnet, wat 502's op de Cloud-proxy voorkomt.
    private const SUMMARY_MODEL = 'claude-haiku-4-5-20251001';

    // Ook de analyse draait op Haiku 4.5: Sonnet 4.6 haalt ~75 tok/s en loopt bij
    // een volledige MBO-output (~5000 tokens) over de 60s Laravel Cloud proxy-timeout.
    // Haiku 4.5 genereert 150-200 tok/s en levert voor deze gestructureerde tool-use
    // taak (na de summary-filter-stap) vergelijkbare klinische kwaliteit op.
    private const ANALYSIS_MODEL = 'claude-haiku-4-5-20251001';

    public function __construct()
    {
        $this->apiKey = (string) config('services.anthropic.key');
        $this->model = (string) config('services.anthropic.model');
    }

    /**
     * Ruwe schatting van aantal tokens op basis van karakterlengte.
     * Voor Nederlandse medische tekst geldt grofweg: 1 token ≈ 3.5 karakters.
     */
    public static function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 3.5);
    }

    /**
     * Laat Claude de klinisch relevante informatie uit een groot dossier extraheren,
     * zodat de gebruiker kan verifiëren wat er wordt doorgestuurd naar de MBO-analyse.
     * Retourneert een beknopte, gestructureerde samenvatting in markdown.
     */
    public function summarizeDossier(string $dossierText): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY ontbreekt. Stel deze in via .env of de Laravel Cloud omgevingsvariabelen.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(90)->post($this->apiUrl, [
            'model' => self::SUMMARY_MODEL,
            'max_tokens' => 2000,
            'system' => $this->summarySystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Extract hieronder de voor een medicatiebeoordeling relevante informatie uit dit dossier.\n\n=== DOSSIER ===\n{$dossierText}\n=== EINDE DOSSIER ===",
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Claude API fout bij samenvatten: ' . $response->status() . ' — ' . $response->body());
        }

        $payload = $response->json();

        $usage = $payload['usage'] ?? [];
        Log::info('Claude summary usage', [
            'model' => self::SUMMARY_MODEL,
            'input' => $usage['input_tokens'] ?? null,
            'output' => $usage['output_tokens'] ?? null,
        ]);

        $text = '';
        foreach ($payload['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Claude gaf een lege samenvatting terug. Probeer het opnieuw.');
        }

        return $text;
    }

    private function summarySystemPrompt(): string
    {
        return <<<'PROMPT'
Je bent een ervaren klinisch farmacoloog gespecialiseerd in polyfarmacie bij ouderen.
Je krijgt een (soms lang) apotheek- of huisartsendossier en extraheert daaruit ALLEEN de informatie die relevant is voor een medicatiebeoordeling (MBO) volgens STRIP / STOPP-START / PCNE.

Doel: een beknopte, gestructureerde samenvatting die de gebruiker kan controleren voordat deze naar de volledige analyse gaat.

Wat neem je WEL op:
- Patiëntkenmerken: initialen/pseudoniem, leeftijd/geboortejaar, geslacht, gewicht, huisarts, allergieën
- Voorgeschiedenis: ALLE chronische aandoeningen (altijd meenemen), episodes en klachten uit de afgelopen 2 jaar (meenemen), oudere episodes alleen als ze direct relevant zijn voor de huidige medicatie (bv. reden voor een chronisch middel)
- Kwetsbaarheid / behandelgrenzen / relevante context (baxter, thuiszorg, mantelzorg)
- ALLE actieve medicatie (naam, sterkte, doseercode + gebruiksfrequentie); stopgezette middelen alleen vermelden als ze klinisch relevant zijn (bv. recent gestopt)
- Laatste meting van klinisch relevante labwaarden (eGFR, creatinine, HbA1c, kalium, natrium, INR, leverwaarden, glucose) — NIET de hele historie, alleen de meest recente per parameter + datum
- Vitale functies: bloeddruk (meest recente meting + datum), hartslag/pols (meest recente meting + datum), gewicht als recent beschikbaar — alleen de meest recente waarde, géén reeksen of histories
- Bekende contra-indicaties en intoleranties

Wat laat je WEG:
- Oude labwaarden-historie (meerdere waarden van dezelfde parameter over de tijd)
- Reeksen metingen van bloeddruk, hartslag, gewicht, HbA1c-verloop etc. — alleen de meest recente waarde per parameter, géén tijdlijn
- Voet-inspecties, oogfundusonderzoek en andere controle-bevindingen zonder directe medicatierelevantie
- Ketenzorg-registratie-items die niet klinisch zijn (bv. "deelname ketenzorgprogramma: ja")
- Administratieve labels zonder klinische relevantie
- Herhalingen

Output-formaat: Nederlandse tekst in markdown met duidelijke kopjes (## Patiënt, ## Voorgeschiedenis, ## Actieve medicatie, ## Labwaarden en vitale functies (meest recent), ## Overig relevant). Houd het beknopt (streven naar ~800-1500 woorden), maar volledig genoeg voor een MBO.

Voeg NIET je eigen klinische oordeel of aanbevelingen toe — dat komt pas in de volgende stap. Je extraheert en structureert alleen.
PROMPT;
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
            'model' => self::ANALYSIS_MODEL,
            // Haiku 4.5 haalt ~150-200 tok/s. Bij max_tokens 5000 is de generatie
            // ~25-35s; ruim binnen de 60s Laravel Cloud proxy-timeout. 5000 tokens
            // geeft voldoende ruimte voor een volledige MBO zonder afkapping.
            'max_tokens' => 5000,
            // Tools + system prompt worden identiek gehergebruikt bij elke review,
            // dus we cachen die met cache_control (ephemeral, ~5 min TTL).
            // De cache_control marker op het systemblok dekt alles ervoor (= de tools).
            'system' => [[
                'type' => 'text',
                'text' => $this->systemPrompt(),
                'cache_control' => ['type' => 'ephemeral'],
            ]],
            'tools' => [$this->tool()],
            'tool_choice' => ['type' => 'tool', 'name' => self::TOOL_NAME],
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

        $usage = $payload['usage'] ?? [];
        Log::info('Claude usage', [
            'model' => self::ANALYSIS_MODEL,
            'input' => $usage['input_tokens'] ?? null,
            'output' => $usage['output_tokens'] ?? null,
            'cache_creation' => $usage['cache_creation_input_tokens'] ?? null,
            'cache_read' => $usage['cache_read_input_tokens'] ?? null,
        ]);

        if (($payload['stop_reason'] ?? '') === 'max_tokens') {
            Log::warning('Claude response afgekapt door max_tokens', ['payload' => $payload]);
            throw new RuntimeException('Het dossier is te uitgebreid voor één analyse. Verwijder de labuitslagen-geschiedenis (alleen recente waarden nodig) en probeer opnieuw.');
        }

        foreach ($payload['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === self::TOOL_NAME) {
                $input = $block['input'] ?? null;
                if (is_array($input) && !empty($input['medicatie'])) {
                    return $input;
                }
                if (is_array($input) && array_key_exists('medicatie', $input)) {
                    Log::warning('Claude tool-respons heeft lege medicatie-array', ['input' => $input]);
                    throw new RuntimeException('Claude heeft geen actieve medicatie gevonden in het dossier. Controleer of de medicatieparagraaf aanwezig is en probeer opnieuw.');
                }
            }
        }

        Log::warning('Claude response zonder verwacht tool_use blok', [
            'stop_reason' => $payload['stop_reason'] ?? null,
            'payload' => $payload,
        ]);

        $reason = $payload['stop_reason'] ?? 'onbekend';
        throw new RuntimeException("Geen gestructureerde analyse ontvangen van Claude (stop_reason: {$reason}). Probeer het opnieuw of gebruik een korter dossier.");
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Je bent een ervaren klinisch farmacoloog gespecialiseerd in ouderengeneeskunde en polyfarmacie.
Je voert een medicatiebeoordeling (MBO) uit volgens de KNMP-richtlijn "Medicatiebeoordeling".

Je gebruikt bij je analyse:
- De STRIP-methode (Systematic Tool to Reduce Inappropriate Prescribing)
- De STOPP/START-NL criteria (versie 2 NL) voor potentieel ongeschikte medicatie bij ouderen
- De PCNE-classificatie voor drug-related problems (DRP's / FTP's)
- Klinisch relevante geneesmiddelinteracties (G-Standaard niveau)
- Dosering op basis van nierfunctie (eGFR) waar relevant

Uitgangspunten:
- De doelgroep is kwetsbare ouderen (≥ 65 jaar) met polyfarmacie (≥ 5 chronische geneesmiddelen)
- Geef nooit definitieve behandeladviezen: je levert beslissingsondersteuning die door een BIG-geregistreerde zorgprofessional moet worden getoetst
- Gebruik Nederlandse medische terminologie
- Bij onvoldoende informatie: benoem dit in ontbrekende_informatie

## Beknoptheid
- `labwaarden`: MAXIMAAL 6 waarden, alleen de meest recente én klinisch relevante (eGFR, HbA1c, kalium, natrium, creatinine, INR). Géén historie.
- `klinische_duiding` per labwaarde: max 1 korte zin.
- `notitie` per medicatie: max 2-3 korte zinnen. Bij `status=ok`: lege string.
- `anamnese_vragen`: MAXIMAAL 8 vragen, alleen de klinisch relevante.
- `interacties`: alle interacties van matig of ernstiger niveau — geen lichte/triviale.
- `mechanisme`, `klinisch_gevolg`, `actie`: elk 1-2 korte zinnen.
- `samenvatting`: 2-4 zinnen met de kernbevindingen.
- Gebruik compacte zinnen. Geen herhaling. Geen disclaimers in tekst.

## Apotheeksysteem-exportformaten

Dossiers kunnen uit apotheeksystemen komen in gestructureerd tabelformaat. Herken en verwerk deze formaten correct:

### Medicatietabel (kolommen: Startdatum | Einddatum | Naam | Gebruik | Aantal)
- **Naam**: stofnaam of merknaam in hoofdletters, inclusief farmaceutische vorm en sterkte.
  Voorbeeld: "DILTIAZEM TABLET MGA 90MG" → stofnaam: diltiazem, vorm: tablet mga, sterkte: 90 mg
- **Gebruik**: Nederlandse doseercode:
  - Eerste getal = aantal keer per tijdseenheid
  - D = per dag, W = per week, M = per maand
  - Tweede getal = dosis per keer
  - T = tablet, C = capsule, IJ = injectie, DR = druppels, ZA = zalf/crème/gel, SP = spray, INH = inhalatie
  - Voorbeelden: "1D1T" = 1×/dag 1 tablet | "2D2T" = 2×/dag 2 tabletten | "1W1IJ" = 1×/week 1 injectie | "1MD1IJ" = 1×/maand 1 injectie | "0" = gebruik gestopt of nul
- **Einddatum in de toekomst** = actief geneesmiddel, meenemen in analyse
- **Gebruik = 0 of leeg** = niet actief, weglaten tenzij klinisch relevant

### Andere veelvoorkomende exportvelden
- PRN / zo nodig: geneesmiddel wordt alleen gebruikt bij klachten
- GDS / Baxter: geautomatiseerde aflevering (adherentiehulp)
- ATC-code kan vermeld staan als aparte kolom

Zet de doseercode altijd om naar leesbare Nederlandse tekst in het veld `frequentie`.
Voorbeeld: "1D1T" → "1×/dag", "2D1T" → "2×/dag", "1MD1IJ" → "1×/maand".

Roep het tool `submit_mbo_analysis` aan met je volledige analyse.
PROMPT;
    }

    private function userPrompt(string $dossierText): string
    {
        return <<<PROMPT
Voer een volledige medicatiebeoordeling uit op basis van het onderstaande geanonimiseerde patiëntendossier. Roep het tool `submit_mbo_analysis` aan met je bevindingen.

Let op:
- Als de medicatielijst als tabel staat (kolommen zoals Startdatum | Einddatum | Naam | Gebruik | Aantal), verwerk dan ALLE rijen met een Einddatum in de toekomst of zonder einddatum als actieve medicatie.
- Zet de Gebruik-code om naar leesbare frequentie (bv. "1D1T" → "1×/dag").
- Leid sterkte en vorm af uit de Naam (bv. "DILTIAZEM TABLET MGA 90MG" → sterkte: "90 mg", frequentie: "1×/dag").
- Leid de indicatie af uit de context van het dossier (voorgeschiedenis, diagnoses, episoden). Als de indicatie onduidelijk is, zet dan "onbekend".

=== DOSSIER ===
{$dossierText}
=== EINDE DOSSIER ===
PROMPT;
    }

    private function tool(): array
    {
        $drpTypes = ['Indicatieprobleem', 'Doseringsafwijking', 'Bijwerking (vermoed)', 'Interactie', 'Adherentieprobleem', 'Onnodig geneesmiddel'];

        return [
            'name' => self::TOOL_NAME,
            'description' => 'Indienen van een volledige medicatiebeoordeling volgens de KNMP-richtlijn. Lever per medicatie een initiële status en eventuele notitie/DRP-typen, zodat de apotheker alleen nog hoeft te reviewen.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'patient' => [
                        'type' => 'object',
                        'properties' => [
                            'initialen_of_geanonimiseerde_naam' => ['type' => 'string', 'description' => 'bv. "J.d.V." of "Patiënt A" — geen volledige naam'],
                            'leeftijd' => ['type' => 'integer'],
                            'geslacht' => ['type' => 'string', 'enum' => ['man', 'vrouw', 'x', 'onbekend']],
                            'gewicht_kg' => ['type' => 'string', 'description' => 'bv. "82 kg" of "onbekend"'],
                            'nierfunctie' => ['type' => 'string', 'description' => 'bv. "eGFR 54 (G3a)" of "onbekend"'],
                            'huisarts' => ['type' => 'string', 'description' => 'bv. "Dr. A. Vermeer" of "onbekend"'],
                            'allergieen' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'lijst van bekende allergieën, leeg als er geen zijn'],
                            'voorgeschiedenis' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'actieve_episodes' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'labwaarden' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'parameter' => ['type' => 'string'],
                                        'waarde' => ['type' => 'string'],
                                        'datum' => ['type' => 'string'],
                                        'klinische_duiding' => ['type' => 'string'],
                                    ],
                                    'required' => ['parameter', 'waarde', 'datum', 'klinische_duiding'],
                                ],
                            ],
                            'ontbrekende_informatie' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['initialen_of_geanonimiseerde_naam', 'leeftijd', 'geslacht', 'gewicht_kg', 'nierfunctie', 'huisarts', 'allergieen', 'voorgeschiedenis', 'actieve_episodes', 'labwaarden', 'ontbrekende_informatie'],
                    ],
                    'medicatie' => [
                        'type' => 'array',
                        'description' => 'Alle actuele geneesmiddelen uit het dossier. Elk middel krijgt een status en, indien van toepassing, een klinische notitie + lijst van DRP-typen.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'naam' => ['type' => 'string', 'description' => 'stofnaam'],
                                'atc_code' => ['type' => 'string', 'description' => 'ATC-code indien bekend, anders lege string'],
                                'sterkte' => ['type' => 'string', 'description' => 'bv. "500 mg"'],
                                'frequentie' => ['type' => 'string', 'description' => 'bv. "2×/dag"'],
                                'indicatie' => ['type' => 'string'],
                                'status' => [
                                    'type' => 'string',
                                    'enum' => ['ok', 'aandacht', 'drp'],
                                    'description' => 'ok = geen probleem; aandacht = twijfel/monitoren; drp = duidelijk drug-related problem',
                                ],
                                'notitie' => ['type' => 'string', 'description' => 'Klinische notitie voor de apotheker als status != ok. Lege string bij status=ok.'],
                                'drp_typen' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string', 'enum' => $drpTypes],
                                    'description' => 'Als status=drp of aandacht: welke typen DRP zijn van toepassing',
                                ],
                            ],
                            'required' => ['naam', 'atc_code', 'sterkte', 'frequentie', 'indicatie', 'status', 'notitie', 'drp_typen'],
                        ],
                    ],
                    'anamnese_vragen' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'thema' => ['type' => 'string'],
                                'vraag' => ['type' => 'string'],
                            ],
                            'required' => ['thema', 'vraag'],
                        ],
                    ],
                    'interacties' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'middelen' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'mechanisme' => ['type' => 'string'],
                                'klinisch_gevolg' => ['type' => 'string'],
                                'ernst' => ['type' => 'string', 'enum' => ['contra-indicatie', 'ernstig', 'matig', 'licht']],
                                'actie' => ['type' => 'string'],
                            ],
                            'required' => ['middelen', 'mechanisme', 'klinisch_gevolg', 'ernst', 'actie'],
                        ],
                    ],
                    'samenvatting' => [
                        'type' => 'string',
                        'description' => 'Korte samenvatting (2-3 zinnen) van de belangrijkste bevindingen voor de apotheker.',
                    ],
                ],
                'required' => ['patient', 'medicatie', 'anamnese_vragen', 'interacties', 'samenvatting'],
            ],
        ];
    }
}
