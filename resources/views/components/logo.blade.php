@props(['inverted' => false, 'height' => 28, 'iconOnly' => false])
@if ($iconOnly)
    {{-- Enkel het icoontje (icon-box), wit op donkere achtergrond of blauw op licht --}}
    @php $bg = $inverted ? 'rgba(255,255,255,0.18)' : '#D6E8FA'; $c = $inverted ? 'white' : '#1A4F9C'; @endphp
    <svg height="{{ $height }}" viewBox="0 0 90 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="0" y="5" width="90" height="90" rx="18" fill="{{ $bg }}"/>
        <text x="9" y="42" font-family="Arial, sans-serif" font-size="26" font-weight="800" fill="{{ $c }}">AI</text>
        <g transform="translate(50,38) rotate(-45)">
            <rect x="-8" y="-22" width="16" height="22" rx="8" fill="{{ $inverted ? 'rgba(255,255,255,0.9)' : 'white' }}"/>
            <rect x="-8" y="0" width="16" height="22" rx="8" fill="{{ $c }}"/>
        </g>
        <circle cx="44" cy="68" r="14" stroke="{{ $c }}" stroke-width="2.5" fill="{{ $inverted ? 'rgba(255,255,255,0.15)' : 'white' }}"/>
        <line x1="34" y1="75" x2="54" y2="61" stroke="{{ $c }}" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
@else
    {{-- Volledig logo: icoontje + woordmerk --}}
    @php
        $textMain = $inverted ? 'white' : '#1A2F6B';
        $textAi   = $inverted ? 'rgba(255,255,255,0.75)' : '#2563C4';
        $bg       = $inverted ? 'rgba(255,255,255,0.18)' : '#D6E8FA';
        $c        = $inverted ? 'white' : '#1A4F9C';
    @endphp
    <svg height="{{ $height }}" viewBox="0 0 440 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:auto">
        <rect x="0" y="5" width="90" height="90" rx="18" fill="{{ $bg }}"/>
        <text x="9" y="42" font-family="Arial, sans-serif" font-size="26" font-weight="800" fill="{{ $c }}">AI</text>
        <g transform="translate(50,38) rotate(-45)">
            <rect x="-8" y="-22" width="16" height="22" rx="8" fill="{{ $inverted ? 'rgba(255,255,255,0.9)' : 'white' }}"/>
            <rect x="-8" y="0" width="16" height="22" rx="8" fill="{{ $c }}"/>
        </g>
        <circle cx="44" cy="68" r="14" stroke="{{ $c }}" stroke-width="2.5" fill="{{ $inverted ? 'rgba(255,255,255,0.15)' : 'white' }}"/>
        <line x1="34" y1="75" x2="54" y2="61" stroke="{{ $c }}" stroke-width="2.5" stroke-linecap="round"/>
        <text x="106" y="66" font-family="Arial, sans-serif" font-size="38" font-weight="700" fill="{{ $textMain }}" letter-spacing="-0.5">medicatiereview</text>
        <text x="374" y="66" font-family="Arial, sans-serif" font-size="38" font-weight="700" fill="{{ $textAi }}" letter-spacing="-0.5">.ai</text>
    </svg>
@endif
