@php
    $isReviewOrRapport = in_array($step, ['review', 'rapport'], true);
@endphp

<div class="flex flex-col min-h-screen bg-[#F7F9FC]">

    {{-- Topbar --}}
    <div class="h-[52px] bg-[#1A4F82] flex items-center px-5 gap-3 shrink-0">
        <x-logo inverted :height="28" />
        <span class="text-white/30 text-[13px]">·</span>
        <span class="text-white/65 text-[13px]">
            @switch($step)
                @case('disclaimer') Welkom @break
                @case('input') Nieuwe medicatiereview @break
                @case('analyzing') Dossier analyseren @break
                @case('review') Medicatiereview @break
                @case('rapport') Rapport @break
                @case('error') Fout @break
            @endswitch
        </span>
        <div class="flex-1"></div>

        @if ($step === 'review')
            <button type="button" wire:click="startOver"
                class="px-3 py-1.5 border border-white/35 rounded-[5px] bg-transparent text-white/85 text-xs font-sans hover:bg-white/10 transition">
                Nieuwe review
            </button>
            <button type="button" wire:click="goToRapport"
                class="px-3.5 py-1.5 rounded-[5px] bg-white/20 text-white text-xs font-medium hover:bg-white/30 transition inline-flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><rect x="2" y="1" width="9" height="11" rx="1.5" stroke="white" stroke-width="1.3"/><path d="M4 4h5M4 6.5h5M4 9h3" stroke="white" stroke-width="1.3" stroke-linecap="round"/></svg>
                Rapport genereren
            </button>
        @endif

        @if ($step === 'rapport')
            <button type="button" wire:click="backToReview"
                class="px-3 py-1.5 border border-white/35 rounded-[5px] bg-transparent text-white/85 text-xs font-sans hover:bg-white/10 transition inline-flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M8 2L3 6.5 8 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Terug naar review
            </button>
            <button type="button" wire:click="downloadPdf"
                class="px-3.5 py-1.5 rounded-[5px] bg-white/20 text-white text-xs font-medium hover:bg-white/30 transition inline-flex items-center gap-1.5">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none"><rect x="3" y="6" width="10" height="7" rx="1.5" stroke="white" stroke-width="1.3"/><path d="M5 6V4a1.5 1.5 0 011.5-1.5h3A1.5 1.5 0 0111 4v2" stroke="white" stroke-width="1.3"/><rect x="5" y="9" width="6" height="1" rx=".5" fill="white"/><rect x="5" y="11" width="4" height="1" rx=".5" fill="white"/></svg>
                Download PDF
            </button>
        @endif

        <div class="w-[30px] h-[30px] rounded-full bg-white/20 flex items-center justify-center text-white text-[11px] font-semibold ml-1">
            {{ strtoupper(substr($apotheek['apotheker'] ?? 'AP', 0, 2)) }}
        </div>
    </div>

    {{-- Body --}}
    @if ($step === 'disclaimer')
        @include('partials.review.disclaimer')
    @elseif ($step === 'input')
        @include('partials.review.input')
    @elseif ($step === 'analyzing')
        @include('partials.review.analyzing')
    @elseif ($step === 'error')
        @include('partials.review.error')
    @elseif ($step === 'review' && $analysis)
        @include('partials.review.review-screen', ['drpTypes' => $drpTypes])
    @elseif ($step === 'rapport' && $analysis)
        @include('partials.review.rapport-screen', ['apotheek' => $apotheek])
    @endif

</div>
