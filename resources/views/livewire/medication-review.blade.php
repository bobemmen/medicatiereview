@php
    $isReviewOrRapport = in_array($step, ['review', 'rapport'], true);
@endphp

<div x-data x-effect="
    const link = document.getElementById('app-favicon');
    if (!link) return;
    const s = $wire.step;
    link.href = (s === 'analyzing' || s === 'summarizing')
        ? '/favicon-analyzing.svg'
        : (s === 'review' || s === 'rapport')
            ? '/favicon-review.svg'
            : '/favicon-default.svg';
" class="flex flex-col min-h-screen bg-[#F7F9FC]">

    {{-- Topbar --}}
    <div class="h-[52px] bg-[#1A4F82] flex items-center px-5 gap-3 shrink-0">
        <x-logo inverted :height="26" />
        <span class="text-white/30 text-[13px]">·</span>
        <span class="text-white/65 text-[13px]">
            @switch($step)
                @case('disclaimer') Welkom @break
                @case('input') Nieuwe medicatiereview @break
                @case('summarizing') Dossier samenvatten @break
                @case('summary_review') Samenvatting controleren @break
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

        {{-- Demo-badge / unlock-status --}}
        @if ($this->isUnlocked)
            <button type="button" wire:click="lockDemo"
                class="ml-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[5px] bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-300/30 text-emerald-50 text-[11px] font-medium transition"
                title="Klik om de demo-limiet opnieuw te activeren">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M6 10V7a6 6 0 1112 0m-9 3h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6a2 2 0 012-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Unlimited
            </button>
        @else
            <button type="button" wire:click="openUnlock"
                class="ml-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[5px] bg-white/15 hover:bg-white/25 border border-white/25 text-white/90 text-[11px] font-medium transition"
                title="Klik om te ontgrendelen met beheerderscode">
                <span class="uppercase tracking-wider">Demo</span>
                <span class="text-white/60">·</span>
                <span>{{ $this->reviewsUsedToday }}/{{ $this->dailyLimit }}</span>
            </button>
        @endif
    </div>

    {{-- Unlock-modal --}}
    @if ($showUnlockModal)
        <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" wire:click.self="closeUnlock">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6" wire:keydown.escape="closeUnlock">
                <div class="flex items-center gap-2 mb-3">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M6 10V7a6 6 0 1112 0m-9 3h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6a2 2 0 012-2z" stroke="#1A4F82" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <h3 class="text-base font-semibold text-[#0F172A]">Demo-limiet ontgrendelen</h3>
                </div>
                <p class="text-xs text-[#64748B] mb-4">Voer de beheerderscode in om het dagelijkse limiet van {{ $this->dailyLimit }} reviews op te heffen voor deze sessie.</p>
                <form wire:submit="submitUnlock">
                    <input type="password" wire:model="unlockInput"
                           placeholder="Beheerderscode"
                           autofocus
                           class="w-full rounded-[5px] border border-[#E2E8F0] bg-white px-3 py-2 text-sm text-[#0F172A] focus:border-[#1A4F82] focus:ring-1 focus:ring-[#1A4F82] outline-none">
                    @if ($unlockError)
                        <p class="text-xs text-[#DC2626] mt-2">{{ $unlockError }}</p>
                    @endif
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" wire:click="closeUnlock"
                            class="px-3 py-1.5 rounded-[5px] border border-[#E2E8F0] bg-white hover:bg-[#F1F5F9] text-[#334155] text-xs font-medium transition">
                            Annuleren
                        </button>
                        <button type="submit"
                            class="px-3 py-1.5 rounded-[5px] bg-[#1A4F82] hover:bg-[#1D5FA0] text-white text-xs font-medium transition">
                            Ontgrendelen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Body --}}
    @if ($step === 'disclaimer')
        @include('partials.review.disclaimer')
    @elseif ($step === 'input')
        @include('partials.review.input')
    @elseif ($step === 'summarizing')
        @include('partials.review.summarizing')
    @elseif ($step === 'summary_review')
        @include('partials.review.summary-review')
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
