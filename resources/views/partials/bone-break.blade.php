@php
    $boneD = 'M 300 93 C 360 93, 420 92, 450 86 C 470 82, 478 64, 490 50 C 500 40, 512 36, 524 36 C 552 36, 576 56, 576 80 C 576 96, 566 106, 556 110 C 566 114, 576 124, 576 140 C 576 164, 552 184, 524 184 C 512 184, 500 180, 490 170 C 478 156, 470 138, 450 134 C 420 128, 360 127, 300 127 C 240 127, 180 128, 150 134 C 130 138, 122 156, 110 170 C 100 180, 88 184, 76 184 C 48 184, 24 164, 24 140 C 24 124, 34 114, 44 110 C 34 106, 24 96, 24 80 C 24 56, 48 36, 76 36 C 88 36, 100 40, 110 50 C 122 64, 130 82, 150 86 C 180 92, 240 93, 300 93 Z';
    $fracture = '304,0 302,94 294,102 307,110 296,118 302,126 300,220';
    // [dx, dy, size, spin] for the bone chips that burst out of the fracture.
    $chips = [[-90, -60, 9, 220], [70, -80, 7, -260], [-120, 10, 6, 180], [110, -20, 8, -200], [-50, -95, 6, 300], [40, -110, 9, -320], [-80, 50, 7, 160], [95, 45, 6, -140], [0, -120, 5, 260], [-130, -40, 5, -300]];
    $confettiColors = ['#f7c948', '#4cc9f0', '#ffffff', '#ffd166', '#7bdff2', '#e9b543'];
@endphp
<div class="break-scene" aria-hidden="true">
    <div class="break-flash"></div>
    <svg class="break-bone" viewBox="14 26 572 168" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="breakGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#fffdf6"/>
                <stop offset="0.5" stop-color="#f3e4c3"/>
                <stop offset="1" stop-color="#cdb07a"/>
            </linearGradient>
            <clipPath id="breakClipL"><polygon points="0,0 {{ $fracture }} 0,220"/></clipPath>
            <clipPath id="breakClipR"><polygon points="600,0 {{ $fracture }} 600,220"/></clipPath>
            @include('partials.fist')
        </defs>
        <path class="whole" d="{{ $boneD }}" fill="url(#breakGrad)" stroke="#6e4f26" stroke-width="7" stroke-linejoin="round"/>
        @foreach (['L', 'R'] as $side)
            <g class="half half-{{ strtolower($side) }}">
                <g clip-path="url(#breakClip{{ $side }})">
                    <path d="{{ $boneD }}" fill="url(#breakGrad)" stroke="#6e4f26" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M 160 100 C 220 98, 380 98, 440 100" fill="none" stroke="#fff" stroke-opacity="0.75" stroke-width="5" stroke-linecap="round"/>
                    <polyline class="edge" points="302,94 294,102 307,110 296,118 302,126" fill="none" stroke="#6e4f26" stroke-width="6" stroke-linejoin="round"/>
                </g>
            </g>
        @endforeach
        <polyline class="crack" points="302,94 294,102 307,110 296,118 302,126" pathLength="1" fill="none" stroke="#3d2810" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        <g class="break-fist">
            <g transform="translate(300 93) scale(1.3)"><use href="#fist"/></g>
        </g>
    </svg>
    <div class="break-hit"></div>
    <div class="break-chips">
        @foreach ($chips as [$dx, $dy, $size, $spin])
            <i style="--dx: {{ $dx }}px; --dy: {{ $dy }}px; --s: {{ $size }}px; --r: {{ $spin }}deg"></i>
        @endforeach
    </div>
</div>
<div class="confetti" aria-hidden="true">
    @for ($i = 0; $i < 28; $i++)
        <i style="--x: {{ ($i * 37) % 100 }}%; --d: {{ 1 + ($i % 7) * 0.18 }}s; --t: {{ 2.4 + ($i % 5) * 0.35 }}s; --c: {{ $confettiColors[$i % count($confettiColors)] }}; --r: {{ ($i % 2 ? 1 : -1) * (360 + $i * 23) }}deg; --sway: {{ (($i * 13) % 60) - 30 }}px"></i>
    @endfor
</div>
