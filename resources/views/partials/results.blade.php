@php
    $p = $analysis['patient_overview'] ?? [];
    $severityColor = fn($s) => match (strtolower($s ?? '')) {
        'hoog', 'contra-indicatie', 'ernstig' => 'bg-red-50 text-red-700 ring-red-200',
        'middel', 'matig' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'laag', 'licht' => 'bg-slate-100 text-slate-700 ring-slate-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<div class="space-y-6">

    <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">1. Patiëntoverzicht</h2>

        <div class="grid grid-cols-2 gap-4 text-sm mb-5">
            <div><span class="text-slate-500">Leeftijd:</span> <strong>{{ $p['leeftijd'] ?? '—' }}</strong></div>
            <div><span class="text-slate-500">Geslacht:</span> <strong>{{ $p['geslacht'] ?? '—' }}</strong></div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 text-sm">
            <div>
                <h3 class="font-medium text-slate-800 mb-2">Voorgeschiedenis</h3>
                <ul class="list-disc pl-5 space-y-1 text-slate-700">
                    @foreach ($p['relevante_voorgeschiedenis'] ?? [] as $item)
                        <li wire:key="vg-{{ $loop->index }}">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h3 class="font-medium text-slate-800 mb-2">Actieve episodes</h3>
                <ul class="list-disc pl-5 space-y-1 text-slate-700">
                    @foreach ($p['actieve_episodes'] ?? [] as $item)
                        <li wire:key="ep-{{ $loop->index }}">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if (!empty($p['relevante_labwaarden']))
            <h3 class="font-medium text-slate-800 mt-6 mb-2 text-sm">Relevante labwaarden</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-3 py-2">Parameter</th>
                            <th class="text-left px-3 py-2">Waarde</th>
                            <th class="text-left px-3 py-2">Datum</th>
                            <th class="text-left px-3 py-2">Duiding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($p['relevante_labwaarden'] as $lab)
                            <tr wire:key="lab-{{ $loop->index }}">
                                <td class="px-3 py-2 font-medium">{{ $lab['parameter'] ?? '' }}</td>
                                <td class="px-3 py-2">{{ $lab['waarde'] ?? '' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $lab['datum'] ?? '' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $lab['klinische_duiding'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (!empty($p['medicatielijst']))
            <h3 class="font-medium text-slate-800 mt-6 mb-2 text-sm">Medicatielijst</h3>
            <ul class="space-y-1 text-sm text-slate-700">
                @foreach ($p['medicatielijst'] as $m)
                    <li wire:key="med-{{ $loop->index }}">
                        <strong>{{ $m['middel'] ?? '' }}</strong>
                        — {{ $m['dosering'] ?? '' }}
                        @if (!empty($m['indicatie_indien_bekend']))
                            <span class="text-slate-500">({{ $m['indicatie_indien_bekend'] }})</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if (!empty($p['ontbrekende_informatie']))
            <div class="mt-5 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm">
                <div class="font-medium text-amber-800 mb-1">Ontbrekende informatie</div>
                <ul class="list-disc pl-5 text-amber-700 space-y-0.5">
                    @foreach ($p['ontbrekende_informatie'] as $item)
                        <li wire:key="ont-{{ $loop->index }}">{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    @if (!empty($analysis['anamnese_vragen']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">2. Anamnese-vragen</h2>
            <p class="text-sm text-slate-500 mb-4">STRIP stap 1 — voor het patiëntgesprek.</p>
            <ul class="space-y-2 text-sm">
                @foreach ($analysis['anamnese_vragen'] as $v)
                    <li wire:key="an-{{ $loop->index }}" class="flex gap-3">
                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200 self-start whitespace-nowrap">{{ $v['thema'] ?? '' }}</span>
                        <span class="text-slate-700">{{ $v['vraag'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (!empty($analysis['drp_analyse']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">3a. Drug-related problems (PCNE)</h2>
            <p class="text-sm text-slate-500 mb-4">STRIP stap 2 — farmacotherapeutische analyse.</p>
            <div class="space-y-3">
                @foreach ($analysis['drp_analyse'] as $d)
                    <div wire:key="drp-{{ $loop->index }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-1">
                            <strong class="text-slate-900">{{ $d['middel'] ?? '' }}</strong>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $severityColor($d['klinische_relevantie'] ?? '') }}">
                                {{ $d['klinische_relevantie'] ?? '' }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-500 mb-2">{{ $d['type_ftp'] ?? '' }}</div>
                        <div class="text-sm text-slate-700">{{ $d['probleem'] ?? '' }}</div>
                        @if (!empty($d['oorzaak']))
                            <div class="text-sm text-slate-500 mt-1"><em>Oorzaak:</em> {{ $d['oorzaak'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($analysis['stopp_start']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">3b. STOPP/START-NL</h2>
            <div class="space-y-3">
                @foreach ($analysis['stopp_start'] as $s)
                    <div wire:key="ss-{{ $loop->index }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-bold
                                {{ ($s['type'] ?? '') === 'STOPP' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $s['type'] ?? '' }} {{ $s['criterium'] ?? '' }}
                            </span>
                            <strong class="text-slate-900">{{ $s['middel_of_klasse'] ?? '' }}</strong>
                        </div>
                        <div class="text-sm text-slate-700 mb-1">{{ $s['bevinding'] ?? '' }}</div>
                        <div class="text-sm text-slate-600"><em>Advies:</em> {{ $s['advies'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($analysis['interacties']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">3c. Interacties</h2>
            <div class="space-y-3">
                @foreach ($analysis['interacties'] as $i)
                    <div wire:key="int-{{ $loop->index }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <strong class="text-slate-900">{{ implode(' + ', $i['middelen'] ?? []) }}</strong>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $severityColor($i['ernst'] ?? '') }}">
                                {{ $i['ernst'] ?? '' }}
                            </span>
                        </div>
                        <div class="text-sm text-slate-700 mb-1"><em>Mechanisme:</em> {{ $i['mechanisme'] ?? '' }}</div>
                        <div class="text-sm text-slate-700 mb-1"><em>Gevolg:</em> {{ $i['klinisch_gevolg'] ?? '' }}</div>
                        <div class="text-sm text-slate-600"><em>Actie:</em> {{ $i['actie'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($analysis['nierfunctie_aandachtspunten']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">3d. Nierfunctie-aandachtspunten</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($analysis['nierfunctie_aandachtspunten'] as $n)
                    <li wire:key="nier-{{ $loop->index }}" class="rounded-lg border border-slate-200 p-3">
                        <strong class="text-slate-900">{{ $n['middel'] ?? '' }}</strong>
                        <span class="text-slate-600"> — {{ $n['advies'] ?? '' }}</span>
                        <div class="text-slate-600 mt-1">{{ $n['toelichting'] ?? '' }}</div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (!empty($analysis['behandelplan']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-1">4. Voorstel behandelplan</h2>
            <p class="text-sm text-slate-500 mb-4">STRIP stap 3 — te bespreken met huisarts en patiënt.</p>
            <div class="space-y-3">
                @foreach ($analysis['behandelplan'] as $b)
                    <div wire:key="bp-{{ $loop->index }}" class="rounded-lg border border-slate-200 p-4">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="inline-flex items-center rounded bg-slate-900 text-white px-2 py-0.5 text-xs font-bold">
                                #{{ $b['prioriteit'] ?? '?' }}
                            </span>
                            <strong class="text-slate-900">{{ $b['middel'] ?? '' }}</strong>
                            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                {{ $b['voorstel'] ?? '' }}
                            </span>
                        </div>
                        <div class="text-sm text-slate-700 mb-1">{{ $b['onderbouwing'] ?? '' }}</div>
                        <div class="text-xs text-slate-500">Bespreken met: {{ $b['bespreken_met'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($analysis['follow_up']))
        <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">5. Follow-up en monitoring</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($analysis['follow_up'] as $f)
                    <li wire:key="fu-{{ $loop->index }}" class="flex gap-3 rounded-lg border border-slate-200 p-3">
                        <div class="flex-1">
                            <strong class="text-slate-900">{{ $f['actie'] ?? '' }}</strong>
                            <div class="text-slate-600 text-xs mt-0.5">Parameter: {{ $f['monitoringparameter'] ?? '' }}</div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200 self-start">
                            {{ $f['termijn'] ?? '' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (!empty($analysis['samenvatting_voor_patient']))
        <section class="rounded-2xl bg-emerald-50 border border-emerald-200 p-6">
            <h2 class="text-lg font-semibold text-emerald-900 mb-3">6. Samenvatting voor de patiënt</h2>
            <p class="text-sm text-emerald-900 leading-relaxed whitespace-pre-line">{{ $analysis['samenvatting_voor_patient'] }}</p>
        </section>
    @endif

</div>
