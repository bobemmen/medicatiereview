@props(['inverted' => false, 'size' => 26])
@php
    $bg = $inverted ? 'rgba(255,255,255,0.18)' : '#EBF3FC';
    $stroke = $inverted ? 'white' : '#1A4F82';
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 26 26" fill="none">
    <rect width="26" height="26" rx="7" fill="{{ $bg }}"/>
    <path d="M13 6v14M6 13h14" stroke="{{ $stroke }}" stroke-width="2.3" stroke-linecap="round"/>
</svg>
