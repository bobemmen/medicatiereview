@php
    $patient = $analysis['patient'] ?? [];
    $meds = $analysis['medicatie'] ?? [];
    $counts = $this->statusCounts;
    $filtered = $this->filteredMeds;
    $sortField = $this->sortField;
    $sortDirection = $this->sortDirection;
    $anamneseVragen = array_slice($analysis['anamnese_vragen'] ?? [], 0, 20);
    $anamneseFirst  = array_slice($anamneseVragen, 0, 10);
    $anamneseExtra  = array_slice($anamneseVragen, 10);

    $statusConfig = [
        'ok' => ['bg' => '#F0FDF4', 'text' => '#166534', 'dot' => '#16A34A', 'label' => 'Akkoord'],
        'aandacht' => ['bg' => '#FFFBEB', 'text' => '#92400E', 'dot' => '#D97706', 'label' => 'Aandacht'],
        'drp' => ['bg' => '#FEF2F2', 'text' => '#991B1B', 'dot' => '#DC2626', 'label' => 'DRP'],
    ];

    $filterTabs = [
        ['key' => 'alle', 'label' => 'Alle', 'count' => $counts['alle']],
        ['key' => 'drp', 'label' => 'DRP', 'count' => $counts['drp']],
        ['key' => 'aandacht', 'label' => 'Aandacht', 'count' => $counts['aandacht']],
        ['key' => 'ok', 'label' => 'Akkoord', 'count' => $counts['ok']],
    ];

    $initials = function ($name) {
        $parts = preg_split('/[\s.]+/', (string) $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return strtoupper(substr(implode('', array_map(fn ($p) => $p[0] ?? '', array_slice($parts, 0, 2))), 0, 2)) ?: '??';
    };
@endphp

<div class="flex flex-1 overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-[260px] bg-white border-r border-[#E2E8F0] p-5 shrink-0 flex flex-col overflow-y-auto">

        <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-3">Patiënt</div>

        <div class="flex items-center gap-3 mb-4">
            <div class="w-[42px] h-[42px] rounded-full bg-[#EBF3FC] flex items-center justify-center text-sm font-bold text-[#1A4F82] shrink-0">
                {{ $initials($patient['initialen_of_geanonimiseerde_naam'] ?? '') }}
            </div>
            <div>
                <div class="text-sm font-semibold text-[#0F172A] leading-tight">
                    {{ $patient['initialen_of_geanonimiseerde_naam'] ?? 'Onbekend' }}
                </div>
                <div class="text-xs text-[#64748B] mt-0.5">
                    {{ $patient['leeftijd'] ?? '—' }} jaar · {{ ucfirst($patient['geslacht'] ?? '') }}
                </div>
            </div>
        </div>

        @foreach ([
            ['Gewicht', $patient['gewicht_kg'] ?? '—'],
            ['Nierfunctie', $patient['nierfunctie'] ?? '—'],
            ['Huisarts', $patient['huisarts'] ?? '—'],
        ] as [$label, $value])
            <div class="mb-2.5">
                <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-0.5">{{ $label }}</div>
                <div class="text-xs text-[#334155]">{{ $value }}</div>
            </div>
        @endforeach

        @if (!empty($patient['allergieen']))
            <div class="border-t border-[#E2E8F0] mt-2.5 pt-3">
                <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-2">Allergie</div>
                <div class="flex flex-wrap gap-1">
                    @foreach ($patient['allergieen'] as $allergie)
                        <span class="bg-[#FEF2F2] text-[#991B1B] text-[11px] px-2 py-0.5 rounded font-medium">{{ $allergie }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($patient['voorgeschiedenis']))
            <div class="border-t border-[#E2E8F0] mt-2.5 pt-3">
                <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-2">Voorgeschiedenis</div>
                <ul class="space-y-1">
                    @foreach ($patient['voorgeschiedenis'] as $item)
                        <li class="text-[11px] text-[#334155] leading-snug">• {{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border-t border-[#E2E8F0] mt-2.5 pt-3">
            <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-2">Deze review</div>
            <div class="flex justify-between mb-1.5 text-xs">
                <span class="text-[#64748B]">Datum</span>
                <span class="text-[#0F172A] font-medium">{{ now()->locale('nl')->isoFormat('D MMM YYYY') }}</span>
            </div>
            <div class="flex justify-between mb-1.5 text-xs">
                <span class="text-[#64748B]">Geneesmiddelen</span>
                <span class="text-[#0F172A] font-medium">{{ count($meds) }}</span>
            </div>
            <div class="flex justify-between mb-1.5 text-xs">
                <span class="text-[#64748B]">DRP's</span>
                <span class="text-[#DC2626] font-bold">{{ $counts['drp'] }}</span>
            </div>
        </div>

        <div class="flex-1"></div>

        {{-- Anamnesevragen-knop + modal --}}
        @if (!empty($anamneseVragen))
            <div x-data="{ open: false, showAll: false }" class="border-t border-[#E2E8F0] mt-2.5 pt-3">
                <button type="button" @click="open = true"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-[6px] bg-[#F3E8FF] text-[#7C3AED] hover:bg-[#EDE9FE] transition text-xs font-medium">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="shrink-0">
                        <path d="M7 1l1.4 3.8L12.5 6 8.4 7.2 7 11 5.6 7.2 1.5 6l4.1-1.2L7 1z" fill="currentColor"/>
                    </svg>
                    Top 10 anamnesevragen
                    <span class="ml-auto text-[10px] bg-[#7C3AED] text-white rounded-full px-1.5 font-semibold">{{ count($anamneseVragen) }}</span>
                </button>

                {{-- Modal overlay --}}
                <div x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-cloak
                    @click.self="open = false"
                    @keydown.escape.window="open = false"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-[2px]">

                    <div @click.stop
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="bg-white rounded-xl shadow-2xl w-[480px] max-w-[92vw] max-h-[80vh] flex flex-col overflow-hidden">

                        {{-- Header --}}
                        <div class="flex items-start justify-between px-5 pt-5 pb-3 border-b border-[#E2E8F0]">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="inline-flex items-center justify-center w-[20px] h-[20px] rounded-full bg-[#F3E8FF]">
                                        <svg width="11" height="11" viewBox="0 0 14 14" fill="none">
                                            <path d="M7 1l1.4 3.8L12.5 6 8.4 7.2 7 11 5.6 7.2 1.5 6l4.1-1.2L7 1z" fill="#7C3AED"/>
                                        </svg>
                                    </span>
                                    <h2 class="text-sm font-semibold text-[#0F172A]">Top 10 anamnesevragen</h2>
                                </div>
                                <p class="text-[11px] text-[#64748B]">AI-selectie op basis van het medicatieprofiel · Ter ondersteuning, niet uitputtend</p>
                            </div>
                            <button type="button" @click="open = false"
                                class="text-[#94A3B8] hover:text-[#0F172A] transition ml-3 mt-0.5 shrink-0">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Lijst --}}
                        <ol class="overflow-y-auto px-5 py-4 space-y-3">
                            @foreach ($anamneseFirst as $i => $vraag)
                                <li class="flex gap-3 items-start">
                                    <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-[#F3E8FF] text-[#7C3AED] text-[10px] font-bold flex items-center justify-center mt-0.5">
                                        {{ $i + 1 }}
                                    </span>
                                    <span class="text-[13px] text-[#334155] leading-snug">{{ $vraag }}</span>
                                </li>
                            @endforeach

                            @if (!empty($anamneseExtra))
                                @foreach ($anamneseExtra as $j => $vraag)
                                    <li x-show="showAll"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 -translate-y-1"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        x-cloak
                                        class="flex gap-3 items-start">
                                        <span class="shrink-0 w-[22px] h-[22px] rounded-full bg-[#EDE9FE] text-[#7C3AED] text-[10px] font-bold flex items-center justify-center mt-0.5">
                                            {{ count($anamneseFirst) + $j + 1 }}
                                        </span>
                                        <span class="text-[13px] text-[#334155] leading-snug">{{ $vraag }}</span>
                                    </li>
                                @endforeach
                            @endif
                        </ol>

                        {{-- Footer --}}
                        <div class="px-5 py-3 border-t border-[#E2E8F0] flex items-center gap-2">
                            @if (!empty($anamneseExtra))
                                <button type="button" x-show="!showAll" @click="showAll = true"
                                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-[6px] bg-[#F3E8FF] text-[#7C3AED] text-xs font-medium hover:bg-[#EDE9FE] transition">
                                    <svg width="11" height="11" viewBox="0 0 14 14" fill="none">
                                        <path d="M7 1l1.4 3.8L12.5 6 8.4 7.2 7 11 5.6 7.2 1.5 6l4.1-1.2L7 1z" fill="currentColor"/>
                                    </svg>
                                    Breid uit naar 20 vragen
                                </button>
                            @endif
                            <div class="flex-1"></div>
                            <button type="button" @click="open = false; showAll = false"
                                class="px-4 py-1.5 rounded-[6px] bg-[#F1F5F9] text-[#64748B] text-xs font-medium hover:bg-[#E2E8F0] transition">
                                Sluiten
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-5 py-2.5 bg-white border-b border-[#E2E8F0] flex items-center gap-3 shrink-0">
            <div class="flex gap-0.5">
                @foreach ($filterTabs as $tab)
                    @php $active = $filter === $tab['key']; @endphp
                    <button type="button" wire:click="setFilter('{{ $tab['key'] }}')"
                        class="px-3 py-1.5 rounded-[5px] text-xs inline-flex items-center gap-1.5 transition
                            {{ $active ? 'bg-[#EBF3FC] text-[#1A4F82] font-semibold' : 'text-[#64748B] font-normal hover:bg-[#F1F5F9]' }}">
                        {{ $tab['label'] }}
                        <span class="text-[10px] font-semibold px-1.5 rounded-full min-w-[18px] text-center
                            {{ $active ? 'bg-[#1A4F82] text-white' : 'bg-[#E2E8F0] text-[#64748B]' }}">
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>
            <div class="flex-1"></div>
            <div class="relative">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[#64748B]">
                    <circle cx="5.5" cy="5.5" r="4" stroke="currentColor" stroke-width="1.3"/>
                    <path d="M9 9l2.5 2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Zoek geneesmiddel..."
                    class="pl-7 pr-3 py-1.5 border border-[#E2E8F0] rounded-[5px] text-xs text-[#0F172A] bg-[#F7F9FC] outline-none focus:border-[#1A4F82] w-[200px]" />
            </div>
        </div>

        {{-- Table head --}}
        <div class="flex bg-[#F1F5F9] border-b border-[#E2E8F0] px-4 shrink-0">
            <div class="w-[4%] py-2 px-1.5"></div>
            <div class="w-[20%] py-2 px-1.5">
                <button type="button" wire:click="setSort('naam')"
                    class="inline-flex items-center gap-1 text-[10px] text-[#64748B] font-medium uppercase tracking-wider hover:text-[#1A4F82] transition-colors">
                    Geneesmiddel
                    <span class="w-2 inline-block text-[#1A4F82]">{{ $sortField === 'naam' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</span>
                </button>
            </div>
            <div class="w-[12%] py-2 px-1.5 text-[10px] text-[#64748B] font-medium uppercase tracking-wider">Dosis</div>
            <div class="w-[20%] py-2 px-1.5 text-[10px] text-[#64748B] font-medium uppercase tracking-wider">Indicatie</div>
            <div class="w-[11%] py-2 px-1.5">
                <button type="button" wire:click="setSort('status')"
                    class="inline-flex items-center gap-1 text-[10px] text-[#64748B] font-medium uppercase tracking-wider hover:text-[#1A4F82] transition-colors">
                    Status
                    <span class="w-2 inline-block text-[#1A4F82]">{{ $sortField === 'status' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}</span>
                </button>
            </div>
            <div class="w-[33%] py-2 px-1.5 text-[10px] text-[#64748B] font-medium uppercase tracking-wider">Aandachtspunt</div>
        </div>

        {{-- Rows --}}
        <div class="flex-1 overflow-y-auto">
            @if (empty($filtered))
                <div class="p-10 text-center text-[#CBD5E1] text-sm italic">Geen geneesmiddelen gevonden.</div>
            @endif

            @foreach ($filtered as $id => $med)
                @php
                    $isExpanded = $expandedMed === $id;
                    $isDone = (bool) ($checked[$id] ?? false);
                    $status = $med['status'] ?? 'ok';
                    $cfg = $statusConfig[$status] ?? $statusConfig['ok'];
                    $activeDrpCount = count(array_filter($drps[$id] ?? []));
                    $currentNote = $notes[$id] ?? '';
                    $aiNote = $med['notitie'] ?? '';
                    $displayNote = $currentNote !== '' ? $currentNote : $aiNote;
                    // Door mens gewijzigd zodra de actuele notitie afwijkt van wat de AI leverde.
                    $isEdited = $displayNote !== '' && $currentNote !== '' && $currentNote !== $aiNote;
                    $expandBg = $status === 'drp' ? '#FFF8F8' : ($status === 'aandacht' ? '#FFFDF5' : '#F5F9FF');
                @endphp

                <div wire:key="med-{{ $id }}"
                    class="border-b border-[#E2E8F0] bg-white relative {{ $isDone ? 'border-l-[3px] border-l-[#16A34A]' : '' }}">

                    {{-- Row --}}
                    <div class="flex items-center px-4 cursor-pointer hover:bg-[#F7F9FC]"
                        wire:click="toggleExpanded({{ $id }})">

                        <div class="w-[4%] py-2.5 flex items-center justify-center">
                            @if ($isDone)
                                <svg width="13" height="13" viewBox="0 0 12 12" fill="none" class="text-[#16A34A]">
                                    <path d="M1.5 6l3 3 6-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @else
                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                    class="transition-transform duration-200 {{ $isExpanded ? 'rotate-90 text-[#1A4F82]' : 'text-[#CBD5E1]' }}">
                                    <path d="M4 2.5l4 3.5-4 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            @endif
                        </div>

                        <div class="w-[20%] py-2.5 px-2">
                            <div class="text-[13px] font-medium text-[#0F172A] leading-tight">{{ $med['naam'] ?? '' }}</div>
                            <div class="text-[10px] text-[#64748B] font-mono mt-0.5">{{ $med['atc_code'] ?? '' }}</div>
                        </div>

                        <div class="w-[12%] py-2.5 px-2">
                            <div class="text-[13px] text-[#334155]">{{ $med['sterkte'] ?? '' }}</div>
                            <div class="text-[11px] text-[#64748B]">{{ $med['frequentie'] ?? '' }}</div>
                        </div>

                        <div class="w-[20%] py-2.5 px-2 text-xs text-[#334155] leading-snug">
                            {{ $med['indicatie'] ?? '' }}
                        </div>

                        <div class="w-[11%] py-2.5 px-2 flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium whitespace-nowrap"
                                style="background: {{ $cfg['bg'] }}; color: {{ $cfg['text'] }};">
                                <span class="w-1 h-1 rounded-full shrink-0" style="background: {{ $cfg['dot'] }};"></span>
                                {{ $cfg['label'] }}
                            </span>
                            @if ($activeDrpCount > 0)
                                <span class="text-[10px] bg-[#FEF2F2] text-[#991B1B] font-semibold px-1.5 rounded-full">{{ $activeDrpCount }}</span>
                            @endif
                        </div>

                        <div class="w-[33%] py-2.5 px-2 text-xs leading-snug
                            {{ $displayNote !== '' ? 'text-[#334155]' : 'text-[#CBD5E1] italic' }}">
                            @if ($displayNote !== '')
                                @if ($isEdited)
                                    <span title="Door apotheker gewijzigd"
                                        class="inline-flex items-center justify-center w-[15px] h-[15px] rounded-full bg-[#EBF3FC] text-[#1A4F82] mr-1 align-text-bottom shrink-0">
                                        <svg width="9" height="9" viewBox="0 0 12 12" fill="none">
                                            <path d="M2 10l1-3 5-5 2 2-5 5-3 1z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                @else
                                    <span title="Door AI opgesteld"
                                        class="inline-flex items-center justify-center w-[15px] h-[15px] rounded-full bg-[#F3E8FF] text-[#7C3AED] mr-1 align-text-bottom shrink-0">
                                        <svg width="9" height="9" viewBox="0 0 12 12" fill="none">
                                            <path d="M6 1l1.2 3.3L10.5 5.5 7.2 6.7 6 10 4.8 6.7 1.5 5.5 4.8 4.3 6 1z" fill="currentColor"/>
                                        </svg>
                                    </span>
                                @endif
                                {{ $displayNote }}
                                @include('partials.review.bronnen-refs', ['bronnen' => $med['bronnen'] ?? []])
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    {{-- Expanded panel --}}
                    @if ($isExpanded)
                        <div class="border-t border-[#E2E8F0] py-4 pr-5 pl-[52px] flex gap-5"
                            style="background: {{ $expandBg }};">

                            {{-- DRP checkboxes --}}
                            <div class="flex-1">
                                <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider mb-2.5">
                                    Drug Related Problems
                                </div>
                                <div class="grid grid-cols-3 gap-1.5">
                                    @foreach ($drpTypes as $type)
                                        @php $on = (bool) ($drps[$id][$type] ?? false); @endphp
                                        <button type="button" wire:click="toggleDrp({{ $id }}, @js($type))"
                                            class="px-2.5 py-1.5 rounded-[5px] border-2 transition flex items-center gap-1.5 text-left
                                                {{ $on ? 'border-[#DC2626] bg-[#FEF2F2]' : 'border-[#E2E8F0] bg-white hover:border-[#CBD5E1]' }}">
                                            <span class="w-[13px] h-[13px] rounded-[3px] border-2 shrink-0 flex items-center justify-center
                                                {{ $on ? 'border-[#DC2626] bg-[#DC2626]' : 'border-[#CBD5E1] bg-transparent' }}">
                                                @if ($on)
                                                    <svg width="8" height="6" viewBox="0 0 8 6" fill="none">
                                                        <path d="M1 3l2 2 4-4" stroke="white" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                @endif
                                            </span>
                                            <span class="text-[11px] leading-tight {{ $on ? 'text-[#991B1B] font-medium' : 'text-[#334155]' }}">{{ $type }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Notes + action --}}
                            <div class="w-[264px] shrink-0 flex flex-col gap-2">
                                <div class="text-[10px] text-[#64748B] font-medium uppercase tracking-wider">Notitie / Advies</div>
                                <textarea wire:model.blur="notes.{{ $id }}"
                                    placeholder="Noteer bevindingen en acties..."
                                    class="w-full h-[80px] px-2.5 py-2 border-2 border-[#E2E8F0] rounded-[5px] text-xs text-[#0F172A] resize-none leading-relaxed bg-white outline-none focus:border-[#1A4F82] transition-colors"></textarea>
                                <button type="button" wire:click="markAsDiscussed({{ $id }})"
                                    class="px-3.5 py-1.5 rounded-[5px] text-xs font-medium inline-flex items-center gap-1.5 transition
                                        {{ $isDone ? 'bg-[#F1F5F9] text-[#64748B] border border-[#E2E8F0]' : 'bg-[#1A4F82] text-white hover:bg-[#1D5FA0]' }}">
                                    @if ($isDone)
                                        <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                                            <path d="M1 5l3.5 3.5L11 1" stroke="#16A34A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Advies gewijzigd
                                    @else
                                        Wijzig advies →
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
