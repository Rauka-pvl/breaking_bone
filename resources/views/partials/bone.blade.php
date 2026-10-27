@php
    $boneD = 'M 300 93 C 360 93, 420 92, 450 86 C 470 82, 478 64, 490 50 C 500 40, 512 36, 524 36 C 552 36, 576 56, 576 80 C 576 96, 566 106, 556 110 C 566 114, 576 124, 576 140 C 576 164, 552 184, 524 184 C 512 184, 500 180, 490 170 C 478 156, 470 138, 450 134 C 420 128, 360 127, 300 127 C 240 127, 180 128, 150 134 C 130 138, 122 156, 110 170 C 100 180, 88 184, 76 184 C 48 184, 24 164, 24 140 C 24 124, 34 114, 44 110 C 34 106, 24 96, 24 80 C 24 56, 48 36, 76 36 C 88 36, 100 40, 110 50 C 122 64, 130 82, 150 86 C 180 92, 240 93, 300 93 Z';

    // Fracture line through the middle of the shaft, top to bottom.
    $fracture = [[303, 10], [298, 88], [307, 96], [294, 104], [309, 112], [296, 121], [305, 130], [300, 210]];
    $jagged = collect($fracture)->map(fn ($p) => implode(',', $p))->implode(' ');
    // The left body reaches under the right half so the intact bone shows no anti-aliased seam.
    $jaggedUnder = collect($fracture)->map(fn ($p) => ($p[0] + 2).','.$p[1])->implode(' ');

    // [appears at progress, path] — the main crack grows from the centre, then branches out.
    $cracks = [
        ['0.04', 'M 300 108 L 307 112 L 296 121'],
        ['0.14', 'M 307 112 L 294 104 L 307 96'],
        ['0.24', 'M 296 121 L 305 130'],
        ['0.32', 'M 307 96 L 298 88'],
        ['0.42', 'M 294 104 L 282 101 L 271 106 L 259 103'],
        ['0.5', 'M 309 112 L 321 115 L 330 111 L 343 114'],
        ['0.58', 'M 296 121 L 285 124 L 276 121'],
        ['0.66', 'M 307 96 L 318 99 L 326 95'],
        ['0.74', 'M 271 106 L 266 114 L 255 117'],
        ['0.82', 'M 330 111 L 336 103 L 348 101'],
        ['0.9', 'M 321 115 L 326 123 L 338 124'],
    ];

    // Hammer fist seen from the front, index on top, pinky (striking edge) at the bottom: [top y, left x, right x].
    $fingers = [[-78, -32, 33], [-59, -33, 35], [-40, -32, 33], [-21, -29, 29]];

    $pores = [[168, 104, 1.6], [196, 117, 1.1], [232, 101, 1.2], [262, 119, 0.9], [352, 103, 1.1], [384, 118, 1.3], [418, 105, 1], [446, 116, 1.4], [62, 150, 1.8], [92, 64, 1.3], [538, 156, 1.6], [508, 66, 1.2]];
@endphp
<svg viewBox="0 -50 600 270" xmlns="http://www.w3.org/2000/svg" aria-label="Жілік">
    <defs>
        <linearGradient id="boneBase" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fffbf2"/>
            <stop offset="0.38" stop-color="#f7ebd2"/>
            <stop offset="0.72" stop-color="#e6d0a6"/>
            <stop offset="1" stop-color="#c7a874"/>
        </linearGradient>
        <linearGradient id="boneUnder" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#8a6534" stop-opacity="0"/>
            <stop offset="1" stop-color="#8a6534" stop-opacity="0.35"/>
        </linearGradient>
        <radialGradient id="knobLight" cx="0.42" cy="0.38" r="0.6">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.75"/>
            <stop offset="0.55" stop-color="#ffffff" stop-opacity="0.18"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="stressGlow" cx="300" cy="110" r="78" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ff5a1f" stop-opacity="0.95"/>
            <stop offset="0.4" stop-color="#ff9a3c" stop-opacity="0.55"/>
            <stop offset="1" stop-color="#ffc56b" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="sheenGrad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0"/>
            <stop offset="0.5" stop-color="#ffffff" stop-opacity="0.65"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </linearGradient>
        <radialGradient id="rayGrad" cx="300" cy="110" r="440" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ffe7a3" stop-opacity="0.95"/>
            <stop offset="0.45" stop-color="#f2c14e" stop-opacity="0.35"/>
            <stop offset="1" stop-color="#f2c14e" stop-opacity="0"/>
        </radialGradient>
        <radialGradient id="haloGrad" cx="300" cy="110" r="190" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff3c8" stop-opacity="0.9"/>
            <stop offset="0.5" stop-color="#f2c14e" stop-opacity="0.3"/>
            <stop offset="1" stop-color="#f2c14e" stop-opacity="0"/>
        </radialGradient>
        <filter id="boneBlur" x="-10%" y="-40%" width="120%" height="180%"><feGaussianBlur stdDeviation="6"/></filter>
        <filter id="boneBlurSm" x="-10%" y="-100%" width="120%" height="300%"><feGaussianBlur stdDeviation="1.6"/></filter>
        <filter id="shadowBlur" x="-20%" y="-200%" width="140%" height="500%"><feGaussianBlur stdDeviation="7"/></filter>

        <path id="bonePath" d="{{ $boneD }}"/>
        <clipPath id="clipBone"><use href="#bonePath"/></clipPath>
        <clipPath id="clipLeft"><polygon points="0,0 {{ $jagged }} 0,220"/></clipPath>
        <clipPath id="clipLeftUnder"><polygon points="0,0 {{ $jaggedUnder }} 0,220"/></clipPath>
        <clipPath id="clipRight"><polygon points="600,0 {{ $jagged }} 600,220"/></clipPath>

        <g id="boneBody">
            <use href="#bonePath" fill="url(#boneBase)"/>
            <g clip-path="url(#clipBone)">
                <rect x="0" y="116" width="600" height="80" fill="url(#boneUnder)"/>
                <use href="#bonePath" fill="none" stroke="#7a5528" stroke-opacity="0.45" stroke-width="20" transform="translate(0 6)" filter="url(#boneBlur)"/>
                <circle cx="68" cy="68" r="38" fill="url(#knobLight)"/>
                <circle cx="532" cy="68" r="38" fill="url(#knobLight)"/>
                <circle cx="72" cy="144" r="30" fill="url(#knobLight)" opacity="0.45"/>
                <circle cx="528" cy="144" r="30" fill="url(#knobLight)" opacity="0.45"/>
                <g fill="none" stroke-linecap="round">
                    <path d="M 160 100 C 230 96.5, 370 96.5, 440 100" stroke="#ffffff" stroke-opacity="0.9" stroke-width="7" filter="url(#boneBlurSm)"/>
                    <path d="M 38 72 C 42 54, 58 44, 78 43" stroke="#ffffff" stroke-opacity="0.85" stroke-width="5" filter="url(#boneBlurSm)"/>
                    <path d="M 562 72 C 558 54, 542 44, 522 43" stroke="#ffffff" stroke-opacity="0.85" stroke-width="5" filter="url(#boneBlurSm)"/>
                    <path d="M 172 107 C 240 105, 360 105, 428 107" stroke="#a88a58" stroke-opacity="0.2" stroke-width="1.3"/>
                    <path d="M 186 115 C 250 113.5, 350 113.5, 414 115" stroke="#a88a58" stroke-opacity="0.16" stroke-width="1.1"/>
                </g>
                <g fill="#8f7346" fill-opacity="0.38">
                    @foreach ($pores as [$cx, $cy, $r])
                        <ellipse cx="{{ $cx }}" cy="{{ $cy }}" rx="{{ $r * 1.6 }}" ry="{{ $r }}"/>
                    @endforeach
                </g>
            </g>
            <use href="#bonePath" fill="none" stroke="#6e4f26" stroke-width="3" stroke-linejoin="round"/>
        </g>

        <linearGradient id="skinGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f8d2ad"/>
            <stop offset="0.5" stop-color="#e8ae80"/>
            <stop offset="1" stop-color="#c7845a"/>
        </linearGradient>
        <linearGradient id="thumbGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fbdcbc"/>
            <stop offset="1" stop-color="#df9f72"/>
        </linearGradient>
        <linearGradient id="sleeveGrad" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0" stop-color="#a3263a"/>
            <stop offset="0.4" stop-color="#7a1528" stop-opacity="0.95"/>
            <stop offset="1" stop-color="#4a0b18" stop-opacity="0"/>
        </linearGradient>

        {{-- Origin is the striking edge of the fist; the chapan sleeve fades out upwards. --}}
        <g id="fist" stroke-linejoin="round" stroke-linecap="round">
            <path d="M -26 -80 L 26 -80 L 31 -175 L -31 -175 Z" fill="url(#sleeveGrad)"/>
            <path d="M -32 -97 L 32 -97 L 31 -78 L -31 -78 Z" fill="#f2c14e" stroke="#9a6b16" stroke-width="1.5"/>
            <path d="M -28 -87.5 L 28 -87.5" fill="none" stroke="#9a6b16" stroke-width="1.2" stroke-dasharray="3 4"/>
            @foreach ([-20, -7, 6, 19] as $ox)
                <path d="M {{ $ox }} -92 l 3.5 4.5 l -3.5 4.5 l -3.5 -4.5 Z" fill="#7a1528"/>
            @endforeach
            <rect x="-37" y="-83" width="74" height="83" rx="22" fill="#c98a5c" stroke="#6b3f22" stroke-width="2.4"/>
            @foreach ($fingers as [$top, $l, $r])
                <rect x="{{ $l }}" y="{{ $top }}" width="{{ $r - $l }}" height="19.5" rx="9.75" fill="url(#skinGrad)" stroke="#6b3f22" stroke-width="2.2"/>
                <ellipse cx="{{ $r - 12 }}" cy="{{ $top + 5.5 }}" rx="6" ry="2.4" fill="#fff" fill-opacity="0.45"/>
                <path d="M {{ $l + 22 }} {{ $top + 4.5 }} q 3.5 5.25 0 10.5" fill="none" stroke="#9c5f38" stroke-width="1.5"/>
            @endforeach
            <path d="M -39 -67 C -22 -78, 5 -75, 18 -65 C 26 -58, 22 -49, 12 -50 C -3 -51, -22 -51, -37 -53 Z" fill="url(#thumbGrad)" stroke="#6b3f22" stroke-width="2.2"/>
            <path d="M 9 -64 C 15 -65, 20 -62, 20 -58 C 20 -54, 14 -53, 10 -55 C 7 -57, 6 -62, 9 -64 Z" fill="#fbe3d3" stroke="#b07a58" stroke-width="1"/>
            <path d="M -14 -57 q 4 -3.5 8 0" fill="none" stroke="#9c5f38" stroke-width="1.4"/>
        </g>
    </defs>

    <ellipse class="bone-shadow" cx="300" cy="204" rx="250" ry="11" fill="#000" opacity="0.4" filter="url(#shadowBlur)"/>

    <g class="bone-rig">
        <g class="bone-rays" opacity="0">
            @for ($i = 0; $i < 18; $i++)
                @php
                    $a = deg2rad($i * 20);
                    $w = deg2rad($i % 2 ? 3 : 5.5);
                    $len = $i % 2 ? 330 : 430;
                @endphp
                <polygon fill="url(#rayGrad)" points="300,110 {{ round(300 + cos($a - $w) * $len, 1) }},{{ round(110 + sin($a - $w) * $len, 1) }} {{ round(300 + cos($a + $w) * $len, 1) }},{{ round(110 + sin($a + $w) * $len, 1) }}"/>
            @endfor
        </g>
        <circle class="bone-halo" cx="300" cy="110" r="190" fill="url(#haloGrad)" opacity="0"/>

        @foreach (['left' => ['clipLeft', 'clipLeftUnder'], 'right' => ['clipRight', 'clipRight']] as $half => [$clip, $bodyClip])
            <g class="bone-half half-{{ $half }}">
                <use href="#boneBody" clip-path="url(#{{ $bodyClip }})"/>
                <g clip-path="url(#{{ $clip }})">
                    <g clip-path="url(#clipBone)">
                        <circle class="stress" cx="300" cy="110" r="78" fill="url(#stressGlow)" opacity="0"/>
                        <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                            @foreach ($cracks as [$at, $d])
                                <g class="crack" data-at="{{ $at }}" stroke-dasharray="1 1" stroke-dashoffset="1">
                                    <path d="{{ $d }}" pathLength="1" stroke="#fff6e0" stroke-opacity="0.7" stroke-width="1.6" transform="translate(0.6 1.6)"/>
                                    <path d="{{ $d }}" pathLength="1" stroke="#3d2810" stroke-width="2.6"/>
                                </g>
                            @endforeach
                        </g>
                        <rect class="sheen" x="-200" y="-20" width="80" height="260" fill="url(#sheenGrad)" transform="skewX(-22)"/>
                    </g>
                </g>
                <g class="edge" opacity="0" clip-path="url(#clipBone)" fill="none" stroke-linejoin="round" stroke-linecap="round">
                    <polyline points="{{ $jagged }}" stroke="#ecd7ab" stroke-width="10"/>
                    <polyline points="{{ $jagged }}" stroke="#b48a52" stroke-width="3.5" stroke-dasharray="1.5 4"/>
                    <polyline points="{{ $jagged }}" stroke="#7a4f2a" stroke-width="1.6"/>
                </g>
            </g>
        @endforeach

        <g class="fist" opacity="0"><use href="#fist" transform="translate(300 58) scale(0.95)"/></g>
    </g>
</svg>
