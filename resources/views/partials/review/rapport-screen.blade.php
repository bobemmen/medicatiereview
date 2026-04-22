@php
    $patient = $analysis['patient'] ?? [];
    $meds = $analysis['medicatie'] ?? [];
    $counts = $this->statusCounts;
    $doneCount = $this->doneCount;

    $statusConfig = [
        'ok' => ['bg' => '#F0FDF4', 'text' => '#166534', 'dot' => '#16A34A', 'label' => 'Akkoord'],
        'aandacht' => ['bg' => '#FFFBEB', 'text' => '#92400E', 'dot' => '#D97706', 'label' => 'Aandacht'],
        'drp' => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'dot' => '#DC2626', 'label' => 'DRP'],
    ];

    $aandachtMeds = array_filter($meds, fn($m) => in_array($m['status'] ?? 'ok', ['drp', 'aandacht'], true));
    $aanbevMeds = [];
    foreach ($meds as $id => $m) {
        $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($m['notitie'] ?? '');
        $hasDrp = !empty(array_filter($drps[$id] ?? []));
        if ($note !== '' || $hasDrp) {
            $aanbevMeds[$id] = $m;
        }
    }
@endphp

<div class="flex-1 overflow-y-auto py-8 px-4 bg-[#EAECF0]">
    <div class="max-w-[780px] mx-auto bg-white shadow-lg rounded-sm">

        {{-- Document header --}}
        <div class="bg-[#1A4F82] p-8 rounded-t-sm">
            <div class="flex items-start justify-between">
                <div>
                    <x-logo inverted :height="22" class="mb-2" />
                    <div class="text-white/60 text-xs mt-2">{{ $apotheek['adres'] }} · {{ $apotheek['telefoon'] }}</div>
                </div>
                <div class="text-right">
                    <div class="text-white/50 text-[10px] uppercase tracking-wider mb-1">Document</div>
                    <div class="text-white text-[17px] font-semibold tracking-tight">Medicatiereview Verslag</div>
                    <div class="text-white/65 text-xs mt-1">{{ now()->locale('nl')->isoFormat('D MMMM YYYY') }}</div>
                </div>
            </div>
        </div>

        <div class="px-9 py-7">

            {{-- Patiëntgegevens --}}
            <div class="mb-6">
                <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Patiëntgegevens</div>
                <div class="grid grid-cols-2 gap-x-6 gap-y-1">
                    @foreach ([
                        ['Patiënt', $patient['initialen_of_geanonimiseerde_naam'] ?? '—'],
                        ['Leeftijd', ($patient['leeftijd'] ?? '—') . ' jaar'],
                        ['Geslacht', ucfirst($patient['geslacht'] ?? '—')],
                        ['Gewicht', $patient['gewicht_kg'] ?? '—'],
                        ['Nierfunctie', $patient['nierfunctie'] ?? '—'],
                        ['Huisarts', $patient['huisarts'] ?? '—'],
                        ['Apotheek', $apotheek['naam']],
                        ['Apotheker', $apotheek['apotheker']],
                    ] as [$k, $v])
                        <div class="flex gap-2 py-1 border-b border-[#F1F5F9]">
                            <span class="text-xs text-[#64748B] min-w-[120px]">{{ $k }}</span>
                            <span class="text-xs text-[#0F172A] font-medium">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
                @if (!empty($patient['allergieen']))
                    <div class="mt-2.5 flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-[#64748B]">Allergie(ën):</span>
                        @foreach ($patient['allergieen'] as $a)
                            <span class="bg-[#FEF2F2] text-[#991B1B] text-[11px] px-2 py-0.5 rounded font-medium">{{ $a }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Voorgeschiedenis --}}
            @if (!empty($patient['voorgeschiedenis']))
                <div class="mb-6">
                    <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Voorgeschiedenis</div>
                    <ul class="list-disc pl-5 text-xs text-[#334155] space-y-1">
                        @foreach ($patient['voorgeschiedenis'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Overzicht geneesmiddelen --}}
            <div class="mb-6">
                <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Overzicht geneesmiddelen</div>
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#F1F5F9]">
                            @foreach (['Geneesmiddel', 'ATC', 'Dosis / Frequentie', 'Indicatie', 'Status'] as $h)
                                <th class="py-1.5 px-2.5 text-left text-[10px] text-[#64748B] font-medium uppercase tracking-wider border border-[#E2E8F0]">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($meds as $i => $med)
                            @php $cfg = $statusConfig[$med['status'] ?? 'ok'] ?? $statusConfig['ok']; @endphp
                            <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#FAFBFC]' }}">
                                <td class="py-2 px-2.5 border border-[#E2E8F0] font-medium text-[#0F172A]">{{ $med['naam'] ?? '' }}</td>
                                <td class="py-2 px-2.5 border border-[#E2E8F0] font-mono text-[#64748B] text-[11px]">{{ $med['atc_code'] ?? '—' }}</td>
                                <td class="py-2 px-2.5 border border-[#E2E8F0] text-[#334155]">{{ $med['sterkte'] ?? '' }} {{ $med['frequentie'] ?? '' }}</td>
                                <td class="py-2 px-2.5 border border-[#E2E8F0] text-[#334155]">{{ $med['indicatie'] ?? '' }}</td>
                                <td class="py-2 px-2.5 border border-[#E2E8F0]">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium"
                                        style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }};">
                                        <span class="w-1 h-1 rounded-full" style="background:{{ $cfg['dot'] }};"></span>
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-2 flex gap-4 text-xs text-[#64748B]">
                    @foreach (['ok', 'aandacht', 'drp'] as $s)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-[7px] h-[7px] rounded-full" style="background: {{ $statusConfig[$s]['dot'] }};"></span>
                            {{ $counts[$s] }}× {{ $statusConfig[$s]['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- DRP / Aandachtspunten --}}
            @if (!empty($aandachtMeds))
                <div class="mb-6">
                    <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Drug Related Problems & Aandachtspunten</div>
                    @foreach ($aandachtMeds as $id => $med)
                        @php
                            $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($med['notitie'] ?? '');
                            $drpList = array_keys(array_filter($drps[$id] ?? []));
                            $cfg = $statusConfig[$med['status'] ?? 'ok'] ?? $statusConfig['ok'];
                            $isLast = $loop->last;
                        @endphp
                        <div class="{{ !$isLast ? 'pb-3.5 mb-3.5 border-b border-[#E2E8F0]' : '' }}">
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span class="text-[13px] font-semibold text-[#0F172A]">{{ $med['naam'] ?? '' }}</span>
                                <span class="text-[11px] text-[#64748B]">{{ $med['sterkte'] ?? '' }} · {{ $med['frequentie'] ?? '' }}</span>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium"
                                    style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }};">
                                    <span class="w-1 h-1 rounded-full" style="background:{{ $cfg['dot'] }};"></span>
                                    {{ $cfg['label'] }}
                                </span>
                            </div>
                            @if ($note)
                                <div class="py-2 px-3 rounded text-xs leading-relaxed mb-1.5"
                                    style="background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }}; border-left: 3px solid {{ $cfg['dot'] }};">
                                    {{ $note }}
                                </div>
                            @endif
                            @if ($drpList)
                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                    @foreach ($drpList as $d)
                                        <span class="text-[10px] px-2 py-0.5 rounded bg-[#FEF2F2] text-[#991B1B] font-medium">{{ $d }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Aanbevelingen --}}
            @if (!empty($aanbevMeds))
                <div class="mb-6">
                    <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Aanbevelingen</div>
                    <ol class="list-decimal pl-5 space-y-1.5">
                        @foreach ($aanbevMeds as $id => $med)
                            @php
                                $note = ($notes[$id] ?? '') !== '' ? $notes[$id] : ($med['notitie'] ?? '');
                                $drpList = array_keys(array_filter($drps[$id] ?? []));
                            @endphp
                            <li class="text-xs text-[#334155] leading-relaxed">
                                <strong class="text-[#0F172A]">{{ $med['naam'] ?? '' }}</strong>
                                @if ($note) — {{ $note }} @endif
                                @if ($drpList)
                                    <span class="text-[#64748B]">({{ implode(', ', $drpList) }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            {{-- Reviewresultaat --}}
            <div class="mb-6">
                <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Reviewresultaat</div>
                <div class="flex gap-3">
                    @foreach ([
                        ['Besproken', "{$doneCount} / " . count($meds), '#0F172A'],
                        ['DRPs', (string) $counts['drp'], '#DC2626'],
                        ['Aandacht', (string) $counts['aandacht'], '#D97706'],
                        ['Akkoord', (string) $counts['ok'], '#16A34A'],
                    ] as [$label, $value, $color])
                        <div class="flex-1 bg-[#F1F5F9] rounded p-3">
                            <div class="text-[10px] text-[#64748B] uppercase tracking-wider mb-1">{{ $label }}</div>
                            <div class="text-xl font-bold" style="color: {{ $color }};">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Handtekening --}}
            <div class="mb-2">
                <div class="text-[10px] text-[#1A4F82] font-semibold uppercase tracking-wider pb-2 border-b-2 border-[#1A4F82] mb-3">Ondertekening</div>
                <div class="grid grid-cols-2 gap-9 mt-1">
                    <div>
                        <div class="text-[11px] text-[#64748B] mb-7">Apotheker</div>
                        <div class="border-b border-[#0F172A] pb-1 text-[13px] text-[#0F172A] font-medium">{{ $apotheek['apotheker'] }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-[#64748B] mb-7">Datum</div>
                        <div class="border-b border-[#0F172A] pb-1 text-[13px] text-[#0F172A] font-medium">{{ now()->locale('nl')->isoFormat('D MMMM YYYY') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="border-t border-[#E2E8F0] px-9 py-3 bg-[#F1F5F9] rounded-b-sm flex justify-between items-center">
            <span class="text-[11px] text-[#64748B]">{{ $apotheek['naam'] }} · {{ $apotheek['adres'] }}</span>
            <span class="text-[11px] text-[#64748B]">Vertrouwelijk — bestemd voor de behandelaar</span>
        </div>
    </div>
</div>
