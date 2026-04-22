@props(['inverted' => false, 'height' => 28, 'iconOnly' => false])
@php
    $aiColor      = $inverted ? 'white'            : '#1A3A7C';
    $capsuleLight = $inverted ? 'rgba(255,255,255,0.75)' : '#4A90D9';
    $capsuleDark  = $inverted ? 'rgba(255,255,255,0.9)'  : '#1A4F9C';
    $tabletFill   = $inverted ? 'rgba(255,255,255,0.85)' : '#1A4F9C';
    $tabletLine   = $inverted ? '#1A4F82'           : 'white';
    $textMain     = $inverted ? 'white'             : '#1A2F6B';
    $textAi       = $inverted ? 'rgba(255,255,255,0.75)' : '#2563C4';
@endphp

@if ($iconOnly)
<svg height="{{ $height }}" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:auto">
    <text x="0" y="72" font-family="Arial, sans-serif" font-size="62" font-weight="900" fill="{{ $aiColor }}" letter-spacing="-2">AI</text>
    <g transform="translate(108,52) rotate(-40)">
        <rect x="-13" y="-30" width="26" height="30" fill="{{ $capsuleLight }}"/>
        <ellipse cx="0" cy="-30" rx="13" ry="13" fill="{{ $capsuleLight }}"/>
        <rect x="-13" y="0" width="26" height="30" fill="{{ $capsuleDark }}"/>
        <ellipse cx="0" cy="30" rx="13" ry="13" fill="{{ $capsuleDark }}"/>
    </g>
    <circle cx="142" cy="82" r="22" fill="{{ $tabletFill }}"/>
    <line x1="125" y1="96" x2="159" y2="68" stroke="{{ $tabletLine }}" stroke-width="3.5" stroke-linecap="round"/>
</svg>
@else
<svg height="{{ $height }}" viewBox="0 0 520 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:auto">
    <text x="0" y="72" font-family="Arial, sans-serif" font-size="62" font-weight="900" fill="{{ $aiColor }}" letter-spacing="-2">AI</text>
    <g transform="translate(108,52) rotate(-40)">
        <rect x="-13" y="-30" width="26" height="30" fill="{{ $capsuleLight }}"/>
        <ellipse cx="0" cy="-30" rx="13" ry="13" fill="{{ $capsuleLight }}"/>
        <rect x="-13" y="0" width="26" height="30" fill="{{ $capsuleDark }}"/>
        <ellipse cx="0" cy="30" rx="13" ry="13" fill="{{ $capsuleDark }}"/>
    </g>
    <circle cx="142" cy="82" r="22" fill="{{ $tabletFill }}"/>
    <line x1="125" y1="96" x2="159" y2="68" stroke="{{ $tabletLine }}" stroke-width="3.5" stroke-linecap="round"/>
    <text x="180" y="83" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="{{ $textMain }}" letter-spacing="-1">medicatiereview</text>
    <text x="463" y="83" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="{{ $textAi }}" letter-spacing="-1">.ai</text>
</svg>
@endif
