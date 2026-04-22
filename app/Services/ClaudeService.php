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
            'max_tokens' => 5000,
            'system' => $this->systemPrompt(),
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

        foreach ($payload['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use' && ($block['name'] ?? '') === self::TOOL_NAME) {
                $input = $block['input'] ?? null;
                if (is_array($input)) {
                    return $input;
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
- Wees beknopt, klinisch concreet en onderbouw bevindingen waar mogelijk met evidence-niveau of richtlijn
- Gebruik Nederlandse medische terminologie
- Bij onvoldoende informatie: benoem dit in ontbrekende_informatie

Roep het tool `submit_mbo_analysis` aan met je volledige analyse.
PROMPT;
    }

    private function userPrompt(string $dossierText): string
    {
        return <<<PROMPT
Voer een volledige medicatiebeoordeling uit op basis van het onderstaande geanonimiseerde patiëntendossier. Roep het tool `submit_mbo_analysis` aan met je bevindingen.

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
