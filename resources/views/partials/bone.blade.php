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

    // Curled fingers of the hammer fist, pinky (striking edge) first; they fan out following the round fist.
    $fingers = ['M -24 -20 Q 8 -21 34 -16', 'M -26 -37 Q 14 -40 44 -35', 'M -26 -55 Q 16 -59 46 -56', 'M -24 -73 Q 14 -78 40 -77'];
    $fingerCrease = [[6, -20], [12, -38], [14, -57], [12, -76]];
    $thumb = 'M -42 -40 C -40 -60, -22 -70, 10 -68';

    $pores = [[168, 104, 1.6], [196, 117, 1.1], [232, 101, 1.2], [262, 119, 0.9], [352, 103, 1.1], [384, 118, 1.3], [418, 105, 1], [446, 116, 1.4], [62, 150, 1.8], [92, 64, 1.3], [538, 156, 1.6], [508, 66, 1.2]];
@endphp
<svg viewBox="0 -70 600 290" xmlns="http://www.w3.org/2000/svg" aria-label="Жілік">
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

        <radialGradient id="palmGrad" cx="0.38" cy="0.32" r="0.75">
            <stop offset="0" stop-color="#e9ac7c"/>
            <stop offset="0.6" stop-color="#d38f61"/>
            <stop offset="1" stop-color="#b06b42"/>
        </radialGradient>
        <linearGradient id="nailGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#fff1e8"/>
            <stop offset="1" stop-color="#efc0a8"/>
        </linearGradient>
        <linearGradient id="goldGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffe9a3"/>
            <stop offset="0.5" stop-color="#e9b543"/>
            <stop offset="1" stop-color="#b47c1c"/>
        </linearGradient>
        <linearGradient id="sleeveGrad" x1="0.85" y1="1" x2="0.15" y2="0">
            <stop offset="0" stop-color="#ad283d"/>
            <stop offset="0.4" stop-color="#86182d" stop-opacity="0.97"/>
            <stop offset="1" stop-color="#4a0b18" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="foldGrad" x1="0.85" y1="1" x2="0.15" y2="0">
            <stop offset="0" stop-color="#4f0b19" stop-opacity="0.65"/>
            <stop offset="1" stop-color="#4f0b19" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="foldLight" x1="0.85" y1="1" x2="0.15" y2="0">
            <stop offset="0" stop-color="#e06276" stop-opacity="0.55"/>
            <stop offset="1" stop-color="#e06276" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="trailGrad" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.9"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </linearGradient>
        <filter id="fistBlur" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="2.4"/></filter>

        {{-- Origin is the striking edge of the fist; the bent chapan sleeve comes from the upper left and fades out. --}}
        <g id="fist" stroke-linejoin="round" stroke-linecap="round">
            <path d="M -42 -84 C -62 -110, -80 -134, -98 -166 C -110 -190, -117 -214, -122 -250 L -62 -254 C -56 -218, -46 -192, -30 -166 C -14 -140, 0 -118, 14 -96 Z" fill="url(#sleeveGrad)"/>
            <path d="M -14 -112 C -30 -136, -46 -164, -60 -204 C -64 -218, -66 -230, -68 -240" fill="none" stroke="url(#foldGrad)" stroke-width="2.6"/>
            <path d="M -40 -110 C -56 -136, -70 -162, -82 -196 C -86 -210, -90 -224, -92 -236" fill="none" stroke="url(#foldGrad)" stroke-width="2.2"/>
            <path d="M -28 -116 C -44 -142, -58 -168, -71 -202" fill="none" stroke="url(#foldLight)" stroke-width="2.4"/>
            <path d="M 2 -108 C -14 -132, -28 -156, -40 -184" fill="none" stroke="url(#foldLight)" stroke-width="1.8"/>

            <path d="M -40 -88 C -10 -100, 34 -94, 46 -66 C 54 -46, 50 -16, 32 -4 C 18 4, -12 4, -30 -6 C -46 -16, -52 -48, -48 -66 C -46 -76, -44 -84, -40 -88 Z" fill="url(#palmGrad)" stroke="#6b3f22" stroke-width="2.3"/>
            <path d="M -44 -62 C -47 -46, -45 -28, -36 -16" fill="none" stroke="#fff" stroke-opacity="0.22" stroke-width="5"/>

            <g transform="translate(-21 -101) rotate(-33)">
                <path d="M -33 -11 Q 0 -14 33 -11 L 33 11 Q 0 14 -33 11 Z" fill="url(#goldGrad)" stroke="#8a5a10" stroke-width="1.4"/>
                <path d="M -31 -6.5 Q 0 -9 31 -6.5 L 31 6.5 Q 0 9 -31 6.5 Z" fill="#7a1528" stroke="#560c1b" stroke-width="0.8"/>
                <g fill="none" stroke="#f2c14e" stroke-width="1.3">
                    @foreach ([-21, -7, 7, 21] as $cx)
                        <path d="M {{ $cx }} 2 c 0 -3.3 -2.4 -5.2 -5.2 -4.3 c -2.5 0.9 -2.3 4 0.2 4.4 M {{ $cx }} 2 c 0 -3.3 2.4 -5.2 5.2 -4.3 c 2.5 0.9 2.3 4 -0.2 4.4 M {{ $cx }} 2 v 3"/>
                    @endforeach
                </g>
                <path d="M -31 -9.5 Q 0 -12.5 31 -9.5" fill="none" stroke="#fff6d0" stroke-opacity="0.8" stroke-width="1.2"/>
            </g>

            @foreach ($fingers as $i => $d)
                <path d="{{ $d }}" fill="none" stroke="#6b3f22" stroke-width="21.5"/>
                <path d="{{ $d }}" fill="none" stroke="#eeaf81" stroke-width="17"/>
                <path d="{{ $d }}" fill="none" stroke="#cf8a5b" stroke-width="8" transform="translate(0 4)"/>
                <path d="{{ $d }}" fill="none" stroke="#ffdcb9" stroke-width="4.5" transform="translate(0 -4.2)"/>
                <path d="M {{ $fingerCrease[$i][0] }} {{ $fingerCrease[$i][1] - 5 }} q 3.2 5 0 10.5" fill="none" stroke="#a0623b" stroke-width="1.4"/>
            @endforeach

            <path d="{{ $thumb }}" fill="none" stroke="#5a2a12" stroke-opacity="0.3" stroke-width="19" transform="translate(2 5)" filter="url(#fistBlur)"/>
            <path d="{{ $thumb }}" fill="none" stroke="#6b3f22" stroke-width="23"/>
            <path d="{{ $thumb }}" fill="none" stroke="#f2b88b" stroke-width="18.5"/>
            <path d="{{ $thumb }}" fill="none" stroke="#d6935f" stroke-width="8" transform="translate(0.5 4.5)"/>
            <path d="{{ $thumb }}" fill="none" stroke="#ffe2c6" stroke-width="5" transform="translate(-0.5 -4.5)"/>
            <path d="M -14 -60 q 3 -4.5 7.5 -1.5" fill="none" stroke="#a0623b" stroke-width="1.4"/>
            <ellipse cx="9" cy="-69.5" rx="6.5" ry="5" transform="rotate(-6 9 -69.5)" fill="url(#nailGrad)" stroke="#b07a58" stroke-width="1.1"/>
            <ellipse cx="10" cy="-71.5" rx="2.8" ry="1.3" fill="#fff" fill-opacity="0.8"/>
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

        <g class="fist" opacity="0">
            <g class="fist-squash">
                <g transform="translate(300 52) scale(1.1)">
                    <g class="fist-trail" opacity="0">
                        <rect x="-66" y="-78" width="4.5" height="66" rx="2.25" fill="url(#trailGrad)"/>
                        <rect x="-58" y="-58" width="3.5" height="46" rx="1.75" fill="url(#trailGrad)"/>
                        <rect x="60" y="-125" width="5" height="105" rx="2.5" fill="url(#trailGrad)"/>
                        <rect x="69" y="-95" width="3.5" height="68" rx="1.75" fill="url(#trailGrad)"/>
                    </g>
                    <use href="#fist"/>
                </g>
            </g>
        </g>
    </g>
</svg>
