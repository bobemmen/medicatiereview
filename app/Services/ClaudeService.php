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
        return [
            'name' => self::TOOL_NAME,
            'description' => 'Indienen van een volledige medicatiebeoordeling volgens de KNMP-richtlijn, inclusief patiëntoverzicht, anamnese-vragen, farmacotherapeutische analyse, behandelplan en follow-up.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'patient_overview' => [
                        'type' => 'object',
                        'properties' => [
                            'leeftijd' => ['type' => 'string'],
                            'geslacht' => ['type' => 'string'],
                            'relevante_voorgeschiedenis' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'actieve_episodes' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'relevante_labwaarden' => [
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
                            'medicatielijst' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'middel' => ['type' => 'string'],
                                        'dosering' => ['type' => 'string'],
                                        'indicatie_indien_bekend' => ['type' => 'string'],
                                    ],
                                    'required' => ['middel', 'dosering', 'indicatie_indien_bekend'],
                                ],
                            ],
                            'ontbrekende_informatie' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['leeftijd', 'geslacht', 'relevante_voorgeschiedenis', 'actieve_episodes', 'relevante_labwaarden', 'medicatielijst', 'ontbrekende_informatie'],
                    ],
                    'anamnese_vragen' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'thema' => ['type' => 'string', 'description' => 'bv. therapietrouw, bijwerkingen, gebruik, zelfmedicatie, wensen patiënt'],
                                'vraag' => ['type' => 'string', 'description' => 'concrete vraag aan de patiënt'],
                            ],
                            'required' => ['thema', 'vraag'],
                        ],
                    ],
                    'drp_analyse' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'middel' => ['type' => 'string'],
                                'type_ftp' => ['type' => 'string', 'description' => 'PCNE-categorie, bv. P1.2 behandeling ongewenst'],
                                'probleem' => ['type' => 'string'],
                                'oorzaak' => ['type' => 'string'],
                                'klinische_relevantie' => ['type' => 'string', 'enum' => ['hoog', 'middel', 'laag']],
                            ],
                            'required' => ['middel', 'type_ftp', 'probleem', 'oorzaak', 'klinische_relevantie'],
                        ],
                    ],
                    'stopp_start' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'criterium' => ['type' => 'string', 'description' => 'STOPP of START nummer/code'],
                                'type' => ['type' => 'string', 'enum' => ['STOPP', 'START']],
                                'middel_of_klasse' => ['type' => 'string'],
                                'bevinding' => ['type' => 'string'],
                                'advies' => ['type' => 'string'],
                            ],
                            'required' => ['criterium', 'type', 'middel_of_klasse', 'bevinding', 'advies'],
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
                    'nierfunctie_aandachtspunten' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'middel' => ['type' => 'string'],
                                'advies' => ['type' => 'string', 'description' => 'dosisaanpassing, staken of monitoren'],
                                'toelichting' => ['type' => 'string'],
                            ],
                            'required' => ['middel', 'advies', 'toelichting'],
                        ],
                    ],
                    'behandelplan' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'prioriteit' => ['type' => 'integer'],
                                'middel' => ['type' => 'string'],
                                'voorstel' => ['type' => 'string', 'enum' => ['staken', 'starten', 'dosisaanpassing', 'wisselen', 'monitoren']],
                                'onderbouwing' => ['type' => 'string'],
                                'bespreken_met' => ['type' => 'string', 'enum' => ['huisarts', 'patient', 'beiden']],
                            ],
                            'required' => ['prioriteit', 'middel', 'voorstel', 'onderbouwing', 'bespreken_met'],
                        ],
                    ],
                    'follow_up' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'actie' => ['type' => 'string'],
                                'monitoringparameter' => ['type' => 'string'],
                                'termijn' => ['type' => 'string'],
                            ],
                            'required' => ['actie', 'monitoringparameter', 'termijn'],
                        ],
                    ],
                    'samenvatting_voor_patient' => [
                        'type' => 'string',
                        'description' => 'Begrijpelijke samenvatting in lekentaal op B1-niveau.',
                    ],
                ],
                'required' => ['patient_overview', 'anamnese_vragen', 'drp_analyse', 'stopp_start', 'interacties', 'nierfunctie_aandachtspunten', 'behandelplan', 'follow_up', 'samenvatting_voor_patient'],
            ],
        ];
    }
}
