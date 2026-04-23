@props(['inverted' => false, 'height' => 28])
<img
    src="{{ asset('img/logo.png') }}"
    alt="medicatiereview.ai"
    style="height: {{ $height }}px; width: auto; max-height: {{ $height }}px; display: inline-block;{{ $inverted ? ' filter: brightness(0) invert(1);' : '' }}"
>
