@php
    $patient = $analysis['patient'] ?? [];
    $meds = $analysis['medicatie'] ?? [];

    $statusConfig = [
        'ok' => ['bg' => '#F0FDF4', 'text' => '#166534', 'dot' => '#16A34A', 'label' => 'Akkoord'],
        'aandacht' => ['bg' => '#FFFBEB', 'text' => '#92400E', 'dot' => '#D97706', 'label' => 'Aandacht'],
        'drp' => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'dot' => '#DC2626', 'label' => 'DRP'],
    ];

    $counts = ['ok' => 0, 'aandacht' => 0, 'drp' => 0];
    foreach ($meds as $med) {
        $s = $med['status'] ?? 'ok';
        if (isset($counts[$s])) {
            $counts[$s]++;
        }
    }
    $doneCount = count(array_filter($checked ?? []));

    $aandachtMeds = array_filter($meds, fn($m) => in_array($m['status'] ?? 'ok', ['drp', 'aandacht'], true));

    $aanbevMeds = [];
    foreach ($meds as $id => $m) {
        $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($m['notitie'] ?? '');
        $hasDrp = !empty(array_filter($drps[$id] ?? []));
        if ($note !== '' || $hasDrp) {
            $aanbevMeds[$id] = $m;
        }
    }

    $dateNl = $generatedAt->locale('nl')->isoFormat('D MMMM YYYY');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Medicatiereview Verslag</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5pt;
            color: #0F172A;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .page { padding: 16mm 16mm 14mm; }

        /* Header */
        .doc-header {
            margin: -16mm -16mm 8mm;
            border-collapse: collapse;
            width: calc(100% + 32mm);
        }
        .doc-header td { vertical-align: middle; }
        .doc-header .logo-cell {
            background: #ffffff;
            padding: 7mm 10mm;
            width: 55mm;
            border-right: 0.5pt solid #E2E8F0;
        }
        .doc-header .title-cell {
            background: #1A4F82;
            padding: 7mm 10mm;
            color: #fff;
        }
        .doc-header .contact { color: #64748B; font-size: 7.5pt; margin-top: 1.5mm; }
        .doc-header .doc-label { color: rgba(255,255,255,0.55); font-size: 7pt; text-transform: uppercase; letter-spacing: 1pt; }
        .doc-header .doc-title { font-size: 12pt; font-weight: bold; letter-spacing: -0.2pt; margin-top: 1mm; }
        .doc-header .doc-date { color: rgba(255,255,255,0.7); font-size: 8.5pt; margin-top: 1mm; }

        /* Section headings */
        h2 {
            font-size: 7.5pt;
            color: #1A4F82;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1pt;
            padding-bottom: 2mm;
            border-bottom: 1.2pt solid #1A4F82;
            margin: 0 0 3mm;
        }
        .section { margin-bottom: 6mm; }

        /* Patient info */
        .patient-grid { width: 100%; border-collapse: collapse; }
        .patient-grid td {
            padding: 1.2mm 0;
            border-bottom: 0.4pt solid #F1F5F9;
            font-size: 8.5pt;
            vertical-align: top;
            width: 50%;
        }
        .patient-grid .label { color: #64748B; display: inline-block; min-width: 28mm; }
        .patient-grid .value { color: #0F172A; font-weight: 500; }

        .allergie-row { margin-top: 2mm; font-size: 8.5pt; }
        .allergie-row .label { color: #64748B; margin-right: 2mm; }
        .allergie { display: inline-block; background: #FEF2F2; color: #991B1B; padding: 0.6mm 2mm; margin-right: 1mm; font-size: 8pt; font-weight: 500; }

        /* Voorgeschiedenis */
        .vg { margin: 0; padding-left: 5mm; font-size: 8.5pt; color: #334155; }
        .vg li { margin-bottom: 0.6mm; }

        /* Medication table */
        .med-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        .med-table th {
            background: #F1F5F9;
            color: #64748B;
            font-size: 7pt;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.6pt;
            padding: 1.5mm 2mm;
            text-align: left;
            border: 0.4pt solid #E2E8F0;
        }
        .med-table td {
            padding: 1.8mm 2mm;
            border: 0.4pt solid #E2E8F0;
            vertical-align: top;
        }
        .med-table tr.alt td { background: #FAFBFC; }
        .med-table .mono { font-family: DejaVu Sans Mono, monospace; color: #64748B; font-size: 7.5pt; }
        .med-name { font-weight: bold; color: #0F172A; }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 0.4mm 1.6mm;
            font-size: 7pt;
            font-weight: 500;
        }
        .badge .dot { display: inline-block; width: 1.2mm; height: 1.2mm; border-radius: 50%; margin-right: 1mm; vertical-align: middle; }

        /* Counts legend */
        .legend { margin-top: 2mm; font-size: 8pt; color: #64748B; }
        .legend span { margin-right: 5mm; }
        .legend .dot { display: inline-block; width: 1.8mm; height: 1.8mm; border-radius: 50%; margin-right: 1mm; vertical-align: middle; }

        /* DRP blocks */
        .drp-block { padding-bottom: 3mm; margin-bottom: 3mm; border-bottom: 0.4pt solid #E2E8F0; }
        .drp-block.last { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
        .drp-head { margin-bottom: 1.5mm; }
        .drp-head .name { font-size: 10pt; font-weight: bold; color: #0F172A; margin-right: 2mm; }
        .drp-head .detail { color: #64748B; font-size: 8pt; margin-right: 2mm; }
        .note-box {
            padding: 1.8mm 2.5mm;
            font-size: 8.5pt;
            line-height: 1.5;
            margin-top: 1.5mm;
        }
        .drp-chip {
            display: inline-block;
            background: #FEF2F2;
            color: #991B1B;
            padding: 0.4mm 1.8mm;
            font-size: 7pt;
            font-weight: 500;
            margin-right: 1mm;
            margin-top: 1.5mm;
        }

        /* Aanbevelingen */
        .aanbev { margin: 0; padding-left: 5mm; font-size: 8.5pt; color: #334155; }
        .aanbev li { margin-bottom: 1.2mm; line-height: 1.5; }
        .aanbev strong { color: #0F172A; }
        .aanbev .drp-list { color: #64748B; }

        /* Review result */
        .result-table { width: 100%; border-collapse: separate; border-spacing: 2mm 0; }
        .result-table td {
            background: #F1F5F9;
            padding: 3mm;
            width: 25%;
            vertical-align: top;
        }
        .result-label { font-size: 7pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.6pt; margin-bottom: 1mm; }
        .result-value { font-size: 14pt; font-weight: bold; }

        /* Ondertekening */
        .sign-table { width: 100%; border-collapse: separate; border-spacing: 8mm 0; margin-top: 1mm; }
        .sign-table td { vertical-align: top; width: 50%; }
        .sign-label { font-size: 8pt; color: #64748B; margin-bottom: 8mm; }
        .sign-value { border-bottom: 0.6pt solid #0F172A; padding-bottom: 1mm; font-size: 9.5pt; font-weight: 500; color: #0F172A; }

        /* Footer */
        .footer {
            background: #F1F5F9;
            border-top: 0.4pt solid #E2E8F0;
            padding: 3mm 16mm;
            margin: 6mm -16mm -14mm;
            font-size: 7.5pt;
            color: #64748B;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { vertical-align: middle; }
        .footer .right { text-align: right; }
    </style>
</head>
<body>

<div class="page">

    {{-- Document header --}}
    <table class="doc-header">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('img/logo.png') }}" height="36" alt="medicatiereview.ai" style="display:block;"/>
                <div class="contact">{{ $apotheek['adres'] }}<br/>{{ $apotheek['telefoon'] }}</div>
            </td>
            <td class="title-cell" style="text-align: right;">
                <div class="doc-label">Document</div>
                <div class="doc-title">Medicatiereview Verslag</div>
                <div class="doc-date">{{ $dateNl }}</div>
            </td>
        </tr>
    </table>

    {{-- Patiëntgegevens --}}
    <div class="section">
        <h2>Patiëntgegevens</h2>
        @php
            $rows = [
                ['Patiënt', $patient['initialen_of_geanonimiseerde_naam'] ?? '—'],
                ['Leeftijd', ($patient['leeftijd'] ?? '—') . ' jaar'],
                ['Geslacht', ucfirst($patient['geslacht'] ?? '—')],
                ['Gewicht', $patient['gewicht_kg'] ?? '—'],
                ['Nierfunctie', $patient['nierfunctie'] ?? '—'],
                ['Huisarts', $patient['huisarts'] ?? '—'],
                ['Apotheek', $apotheek['naam']],
                ['Apotheker', $apotheek['apotheker']],
            ];
            $pairs = array_chunk($rows, 2);
        @endphp
        <table class="patient-grid">
            @foreach ($pairs as $pair)
                <tr>
                    @foreach ($pair as [$k, $v])
                        <td><span class="label">{{ $k }}</span><span class="value">{{ $v }}</span></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
        @if (!empty($patient['allergieen']))
            <div class="allergie-row">
                <span class="label">Allergie(ën):</span>
                @foreach ($patient['allergieen'] as $a)
                    <span class="allergie">{{ $a }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Voorgeschiedenis --}}
    @if (!empty($patient['voorgeschiedenis']))
        <div class="section">
            <h2>Voorgeschiedenis</h2>
            <ul class="vg">
                @foreach ($patient['voorgeschiedenis'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Overzicht geneesmiddelen --}}
    <div class="section">
        <h2>Overzicht geneesmiddelen</h2>
        <table class="med-table">
            <thead>
                <tr>
                    <th>Geneesmiddel</th>
                    <th>ATC</th>
                    <th>Dosis / Frequentie</th>
                    <th>Indicatie</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meds as $i => $med)
                    @php $cfg = $statusConfig[$med['status'] ?? 'ok'] ?? $statusConfig['ok']; @endphp
                    <tr @if ($i % 2 === 1) class="alt" @endif>
                        <td class="med-name">{{ $med['naam'] ?? '' }}</td>
                        <td class="mono">{{ $med['atc_code'] ?? '—' }}</td>
                        <td>{{ trim(($med['sterkte'] ?? '') . ' ' . ($med['frequentie'] ?? '')) }}</td>
                        <td>{{ $med['indicatie'] ?? '' }}</td>
                        <td>
                            <span class="badge" style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }};">
                                <span class="dot" style="background:{{ $cfg['dot'] }};"></span>{{ $cfg['label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="legend">
            @foreach (['ok', 'aandacht', 'drp'] as $s)
                <span>
                    <span class="dot" style="background:{{ $statusConfig[$s]['dot'] }};"></span>{{ $counts[$s] }}× {{ $statusConfig[$s]['label'] }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- DRP / Aandachtspunten --}}
    @if (!empty($aandachtMeds))
        <div class="section">
            <h2>Drug Related Problems &amp; Aandachtspunten</h2>
            @foreach ($aandachtMeds as $id => $med)
                @php
                    $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($med['notitie'] ?? '');
                    $drpList = array_keys(array_filter($drps[$id] ?? []));
                    $cfg = $statusConfig[$med['status'] ?? 'ok'] ?? $statusConfig['ok'];
                    $isLast = $loop->last;
                @endphp
                <div class="drp-block {{ $isLast ? 'last' : '' }}">
                    <div class="drp-head">
                        <span class="name">{{ $med['naam'] ?? '' }}</span>
                        <span class="detail">{{ $med['sterkte'] ?? '' }} · {{ $med['frequentie'] ?? '' }}</span>
                        <span class="badge" style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }};">
                            <span class="dot" style="background:{{ $cfg['dot'] }};"></span>{{ $cfg['label'] }}
                        </span>
                    </div>
                    @if ($note)
                        <div class="note-box" style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }}; border-left: 1.2pt solid {{ $cfg['dot'] }};">
                            {{ $note }}
                        </div>
                    @endif
                    @if ($drpList)
                        <div>
                            @foreach ($drpList as $d)
                                <span class="drp-chip">{{ $d }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Aanbevelingen --}}
    @if (!empty($aanbevMeds))
        <div class="section">
            <h2>Aanbevelingen</h2>
            <ol class="aanbev">
                @foreach ($aanbevMeds as $id => $med)
                    @php
                        $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($med['notitie'] ?? '');
                        $drpList = array_keys(array_filter($drps[$id] ?? []));
                    @endphp
                    <li>
                        <strong>{{ $med['naam'] ?? '' }}</strong>@if ($note) — {{ $note }}@endif
                        @if ($drpList)
                            <span class="drp-list">({{ implode(', ', $drpList) }})</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- Reviewresultaat --}}
    <div class="section">
        <h2>Reviewresultaat</h2>
        @php
            $resultCards = [
                ['Besproken', $doneCount . ' / ' . count($meds), '#0F172A'],
                ['DRPs', (string) $counts['drp'], '#DC2626'],
                ['Aandacht', (string) $counts['aandacht'], '#D97706'],
                ['Akkoord', (string) $counts['ok'], '#16A34A'],
            ];
        @endphp
        <table class="result-table">
            <tr>
                @foreach ($resultCards as [$label, $value, $color])
                    <td>
                        <div class="result-label">{{ $label }}</div>
                        <div class="result-value" style="color: {{ $color }};">{{ $value }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- Ondertekening --}}
    <div class="section">
        <h2>Ondertekening</h2>
        <table class="sign-table">
            <tr>
                <td>
                    <div class="sign-label">Apotheker</div>
                    <div class="sign-value">{{ $apotheek['apotheker'] }}</div>
                </td>
                <td>
                    <div class="sign-label">Datum</div>
                    <div class="sign-value">{{ $dateNl }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <table>
            <tr>
                <td>{{ $apotheek['naam'] }} · {{ $apotheek['adres'] }}</td>
                <td class="right">Vertrouwelijk — bestemd voor de behandelaar</td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
