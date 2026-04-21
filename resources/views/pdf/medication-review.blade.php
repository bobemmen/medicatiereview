@php
    $p = $analysis['patient_overview'] ?? [];
    $badge = function ($severity) {
        $s = strtolower($severity ?? '');
        return match (true) {
            in_array($s, ['hoog', 'contra-indicatie', 'ernstig']) => ['#fef2f2', '#b91c1c'],
            in_array($s, ['middel', 'matig']) => ['#fffbeb', '#b45309'],
            default => ['#f1f5f9', '#475569'],
        };
    };
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Medicatiebeoordeling</title>
    <style>
        @page { margin: 22mm 18mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.45;
        }
        h1 { font-size: 18pt; color: #0f172a; margin: 0 0 4pt; }
        h2 { font-size: 12pt; color: #0f172a; margin: 18pt 0 6pt; padding-bottom: 3pt; border-bottom: 1pt solid #e2e8f0; }
        h3 { font-size: 10pt; color: #334155; margin: 10pt 0 4pt; }
        p { margin: 0 0 6pt; }
        ul { margin: 2pt 0 6pt 16pt; padding: 0; }
        li { margin-bottom: 2pt; }
        .subtitle { color: #64748b; font-size: 9pt; margin-bottom: 10pt; }
        .meta { color: #64748b; font-size: 8.5pt; }
        .card {
            border: 0.5pt solid #e2e8f0;
            border-radius: 4pt;
            padding: 6pt 8pt;
            margin-bottom: 6pt;
        }
        .strong { font-weight: bold; color: #0f172a; }
        .muted { color: #64748b; }
        .em { font-style: italic; }
        .badge {
            display: inline-block;
            padding: 1pt 5pt;
            border-radius: 3pt;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-stopp { background: #fee2e2; color: #b91c1c; }
        .badge-start { background: #d1fae5; color: #065f46; }
        .badge-prio { background: #0f172a; color: #fff; }
        .badge-type { background: #f1f5f9; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin: 4pt 0 8pt; font-size: 9pt; }
        th, td { border: 0.5pt solid #e2e8f0; padding: 3pt 5pt; text-align: left; vertical-align: top; }
        th { background: #f8fafc; color: #475569; font-weight: bold; }
        .disclaimer {
            margin-top: 14pt;
            padding: 8pt;
            border: 0.5pt solid #fde68a;
            background: #fffbeb;
            font-size: 8.5pt;
            color: #78350f;
        }
        .summary {
            margin-top: 10pt;
            padding: 8pt;
            border: 0.5pt solid #a7f3d0;
            background: #ecfdf5;
            color: #065f46;
            font-size: 9.5pt;
        }
        .row { margin-bottom: 5pt; }
    </style>
</head>
<body>

    <h1>Medicatiebeoordeling</h1>
    <p class="subtitle">AI-ondersteuning volgens KNMP-richtlijn — gegenereerd {{ now()->format('d-m-Y H:i') }}</p>

    <h2>1. Patiëntoverzicht</h2>

    <div class="row"><span class="muted">Leeftijd:</span> <span class="strong">{{ $p['leeftijd'] ?? '—' }}</span></div>
    <div class="row"><span class="muted">Geslacht:</span> <span class="strong">{{ $p['geslacht'] ?? '—' }}</span></div>

    @if (!empty($p['relevante_voorgeschiedenis']))
        <h3>Voorgeschiedenis</h3>
        <ul>
            @foreach ($p['relevante_voorgeschiedenis'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    @if (!empty($p['actieve_episodes']))
        <h3>Actieve episodes</h3>
        <ul>
            @foreach ($p['actieve_episodes'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    @if (!empty($p['relevante_labwaarden']))
        <h3>Relevante labwaarden</h3>
        <table>
            <thead>
                <tr><th>Parameter</th><th>Waarde</th><th>Datum</th><th>Duiding</th></tr>
            </thead>
            <tbody>
                @foreach ($p['relevante_labwaarden'] as $lab)
                    <tr>
                        <td class="strong">{{ $lab['parameter'] ?? '' }}</td>
                        <td>{{ $lab['waarde'] ?? '' }}</td>
                        <td class="muted">{{ $lab['datum'] ?? '' }}</td>
                        <td>{{ $lab['klinische_duiding'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($p['medicatielijst']))
        <h3>Medicatielijst</h3>
        <ul>
            @foreach ($p['medicatielijst'] as $m)
                <li>
                    <span class="strong">{{ $m['middel'] ?? '' }}</span>
                    — {{ $m['dosering'] ?? '' }}
                    @if (!empty($m['indicatie_indien_bekend']))
                        <span class="muted">({{ $m['indicatie_indien_bekend'] }})</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if (!empty($p['ontbrekende_informatie']))
        <h3>Ontbrekende informatie</h3>
        <ul>
            @foreach ($p['ontbrekende_informatie'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @endif

    @if (!empty($analysis['anamnese_vragen']))
        <h2>2. Anamnese-vragen (STRIP stap 1)</h2>
        <ul>
            @foreach ($analysis['anamnese_vragen'] as $v)
                <li>
                    <span class="badge badge-type">{{ $v['thema'] ?? '' }}</span>
                    {{ $v['vraag'] ?? '' }}
                </li>
            @endforeach
        </ul>
    @endif

    @if (!empty($analysis['drp_analyse']))
        <h2>3a. Drug-related problems (PCNE)</h2>
        @foreach ($analysis['drp_analyse'] as $d)
            @php [$bg, $color] = $badge($d['klinische_relevantie'] ?? ''); @endphp
            <div class="card">
                <div>
                    <span class="strong">{{ $d['middel'] ?? '' }}</span>
                    <span class="badge" style="background:{{ $bg }}; color:{{ $color }}">{{ $d['klinische_relevantie'] ?? '' }}</span>
                </div>
                <div class="muted" style="font-size:8.5pt; margin:1pt 0 3pt;">{{ $d['type_ftp'] ?? '' }}</div>
                <div>{{ $d['probleem'] ?? '' }}</div>
                @if (!empty($d['oorzaak']))
                    <div class="muted" style="margin-top:2pt;"><span class="em">Oorzaak:</span> {{ $d['oorzaak'] }}</div>
                @endif
            </div>
        @endforeach
    @endif

    @if (!empty($analysis['stopp_start']))
        <h2>3b. STOPP/START-NL</h2>
        @foreach ($analysis['stopp_start'] as $s)
            <div class="card">
                <div>
                    <span class="badge {{ ($s['type'] ?? '') === 'STOPP' ? 'badge-stopp' : 'badge-start' }}">
                        {{ $s['type'] ?? '' }} {{ $s['criterium'] ?? '' }}
                    </span>
                    <span class="strong">{{ $s['middel_of_klasse'] ?? '' }}</span>
                </div>
                <div style="margin-top:3pt;">{{ $s['bevinding'] ?? '' }}</div>
                <div class="muted" style="margin-top:2pt;"><span class="em">Advies:</span> {{ $s['advies'] ?? '' }}</div>
            </div>
        @endforeach
    @endif

    @if (!empty($analysis['interacties']))
        <h2>3c. Interacties</h2>
        @foreach ($analysis['interacties'] as $i)
            @php [$bg, $color] = $badge($i['ernst'] ?? ''); @endphp
            <div class="card">
                <div>
                    <span class="strong">{{ implode(' + ', $i['middelen'] ?? []) }}</span>
                    <span class="badge" style="background:{{ $bg }}; color:{{ $color }}">{{ $i['ernst'] ?? '' }}</span>
                </div>
                <div style="margin-top:3pt;"><span class="em">Mechanisme:</span> {{ $i['mechanisme'] ?? '' }}</div>
                <div><span class="em">Gevolg:</span> {{ $i['klinisch_gevolg'] ?? '' }}</div>
                <div class="muted"><span class="em">Actie:</span> {{ $i['actie'] ?? '' }}</div>
            </div>
        @endforeach
    @endif

    @if (!empty($analysis['nierfunctie_aandachtspunten']))
        <h2>3d. Nierfunctie-aandachtspunten</h2>
        <ul>
            @foreach ($analysis['nierfunctie_aandachtspunten'] as $n)
                <li>
                    <span class="strong">{{ $n['middel'] ?? '' }}</span>
                    — {{ $n['advies'] ?? '' }}: {{ $n['toelichting'] ?? '' }}
                </li>
            @endforeach
        </ul>
    @endif

    @if (!empty($analysis['behandelplan']))
        <h2>4. Voorstel behandelplan (STRIP stap 3)</h2>
        @foreach ($analysis['behandelplan'] as $b)
            <div class="card">
                <div>
                    <span class="badge badge-prio">#{{ $b['prioriteit'] ?? '?' }}</span>
                    <span class="strong">{{ $b['middel'] ?? '' }}</span>
                    <span class="badge badge-type">{{ $b['voorstel'] ?? '' }}</span>
                </div>
                <div style="margin-top:3pt;">{{ $b['onderbouwing'] ?? '' }}</div>
                <div class="meta" style="margin-top:3pt;">Bespreken met: {{ $b['bespreken_met'] ?? '' }}</div>
            </div>
        @endforeach
    @endif

    @if (!empty($analysis['follow_up']))
        <h2>5. Follow-up en monitoring</h2>
        <table>
            <thead>
                <tr><th>Actie</th><th>Parameter</th><th>Termijn</th></tr>
            </thead>
            <tbody>
                @foreach ($analysis['follow_up'] as $f)
                    <tr>
                        <td class="strong">{{ $f['actie'] ?? '' }}</td>
                        <td>{{ $f['monitoringparameter'] ?? '' }}</td>
                        <td>{{ $f['termijn'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($analysis['samenvatting_voor_patient']))
        <h2>6. Samenvatting voor de patiënt</h2>
        <div class="summary">{{ $analysis['samenvatting_voor_patient'] }}</div>
    @endif

    <div class="disclaimer">
        <span class="strong">Disclaimer:</span> dit verslag is gegenereerd met AI-ondersteuning en bedoeld als beslissingsondersteuning.
        De BIG-geregistreerde apotheker/arts blijft verantwoordelijk voor het definitieve oordeel en behandelbeleid.
        Er is geen klinische validatie of CE-markering (MDR).
    </div>

</body>
</html>
