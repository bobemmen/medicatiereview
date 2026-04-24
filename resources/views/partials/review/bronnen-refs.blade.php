@props(['bronnen' => []])

@if (!empty($bronnen))
    <span class="inline-flex flex-wrap items-baseline gap-x-0.5 ml-0.5 align-baseline">
        @foreach ($bronnen as $bron)
            @php
                $type = $bron['type'] ?? 'Overig';
                $titel = $bron['titel'] ?? '';
                $toelichting = $bron['toelichting'] ?? '';
            @endphp
            <span x-data="{ open: false }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                class="relative inline-block">
                <button type="button"
                    @click.stop="open = !open"
                    class="text-[10px] font-semibold text-[#1A4F82] hover:text-[#1D5FA0] hover:underline cursor-pointer align-baseline focus:outline-none focus:ring-1 focus:ring-[#1A4F82] rounded-sm px-0.5">({{ $loop->iteration }})</button>
                <div x-show="open"
                    x-transition.opacity.duration.100ms
                    x-cloak
                    @click.stop
                    class="absolute z-30 left-0 mt-1 w-64 bg-white border border-[#E2E8F0] rounded-md shadow-lg p-2.5 text-left normal-case">
                    <div class="text-[9px] font-semibold text-[#1A4F82] uppercase tracking-wider">{{ $type }}</div>
                    <div class="text-xs font-semibold text-[#0F172A] mt-0.5 leading-snug">{{ $titel }}</div>
                    @if ($toelichting !== '')
                        <div class="text-[11px] text-[#334155] mt-1 leading-relaxed">{{ $toelichting }}</div>
                    @endif
                </div>
            </span>
        @endforeach
    </span>
@endif
