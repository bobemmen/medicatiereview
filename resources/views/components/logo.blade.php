@props(['inverted' => false, 'height' => 28])
<img
    src="{{ asset('img/logo.png') }}"
    height="{{ $height }}"
    alt="medicatiereview.ai"
    style="width: auto; display: inline-block;{{ $inverted ? ' filter: brightness(0) invert(1);' : '' }}"
>
