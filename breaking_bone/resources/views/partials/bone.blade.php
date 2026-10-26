@php
    $boneD = 'M 120 88 C 220 96, 380 96, 480 88 C 500 72, 510 46, 542 46 C 580 46, 596 76, 576 98 C 568 105, 568 115, 576 122 C 596 144, 580 174, 542 174 C 510 174, 500 148, 480 132 C 380 124, 220 124, 120 132 C 100 148, 90 174, 58 174 C 20 174, 4 144, 24 122 C 32 115, 32 105, 24 98 C 4 76, 20 46, 58 46 C 90 46, 100 72, 120 88 Z';
    $jagged = '302,30 292,92 309,101 289,110 311,118 294,127 304,190';
    $cracks = [
        ['0.08', 'M 300 94 L 296 102 L 304 108 L 298 116 L 303 126'],
        ['0.22', 'M 298 104 L 286 100 L 276 106 L 262 102'],
        ['0.36', 'M 304 112 L 318 108 L 330 114 L 344 110'],
        ['0.5', 'M 300 120 L 289 124 L 278 121 L 268 125'],
        ['0.62', 'M 302 100 L 314 96 L 324 99 L 334 95'],
        ['0.72', 'M 276 106 L 270 116 L 258 118'],
        ['0.82', 'M 330 114 L 338 122 L 352 121'],
        ['0.9', 'M 292 92 L 309 101 L 289 110 L 311 118 L 294 127'],
    ];
@endphp
<svg viewBox="0 0 600 220" xmlns="http://www.w3.org/2000/svg" aria-label="Жілік">
    <defs>
        <linearGradient id="boneGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fffdf5"/>
            <stop offset="0.42" stop-color="#f4e7ca"/>
            <stop offset="0.8" stop-color="#d9c398"/>
            <stop offset="1" stop-color="#b39766"/>
        </linearGradient>
        <radialGradient id="knobShade" cx="0.5" cy="0.35" r="0.6">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.55"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <path id="bonePath" d="{{ $boneD }}"/>
        <clipPath id="clipBone"><use href="#bonePath"/></clipPath>
        <clipPath id="clipLeft"><polygon points="0,0 302,0 {{ $jagged }} 304,220 0,220"/></clipPath>
        <clipPath id="clipRight"><polygon points="304,220 304,190 294,127 311,118 289,110 309,101 292,92 302,30 302,0 600,0 600,220"/></clipPath>
        <g id="boneBody">
            <use href="#bonePath" fill="url(#boneGrad)" stroke="#6b5530" stroke-width="3.5" stroke-linejoin="round"/>
            <g clip-path="url(#clipBone)">
                <ellipse cx="56" cy="80" rx="26" ry="22" fill="url(#knobShade)"/>
                <ellipse cx="544" cy="80" rx="26" ry="22" fill="url(#knobShade)"/>
                <path d="M 132 97 C 222 104, 378 104, 468 97" fill="none" stroke="#fff" stroke-opacity="0.7" stroke-width="5" stroke-linecap="round"/>
                <path d="M 140 124 C 230 117, 370 117, 460 124" fill="none" stroke="#8a7044" stroke-opacity="0.35" stroke-width="4" stroke-linecap="round"/>
                <g fill="#9c8355" fill-opacity="0.45">
                    <circle cx="170" cy="108" r="1.6"/><circle cx="214" cy="114" r="1.2"/><circle cx="388" cy="106" r="1.4"/>
                    <circle cx="430" cy="116" r="1.2"/><circle cx="60" cy="140" r="2"/><circle cx="540" cy="146" r="1.8"/>
                </g>
            </g>
        </g>
    </defs>

    <ellipse class="bone-shadow" cx="300" cy="200" rx="250" ry="10" fill="#000" opacity="0.35"/>

    @foreach (['left' => 'clipLeft', 'right' => 'clipRight'] as $half => $clip)
        <g class="bone-half half-{{ $half }}">
            <g clip-path="url(#{{ $clip }})">
                <use href="#boneBody"/>
                <g clip-path="url(#clipBone)" fill="none" stroke="#3f2b10" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                    @foreach ($cracks as [$at, $d])
                        <path class="crack" data-at="{{ $at }}" pathLength="1" stroke-dasharray="1 1" stroke-dashoffset="1" d="{{ $d }}"/>
                    @endforeach
                </g>
            </g>
            <polyline class="edge" clip-path="url(#clipBone)" points="{{ $jagged }}" fill="none" stroke="#9c4f36" stroke-width="5" stroke-linejoin="round" opacity="0"/>
        </g>
    @endforeach
</svg>
