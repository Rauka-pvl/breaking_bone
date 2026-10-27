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

    // Curled fingers of the hammer fist seen from the palm side, pinky (striking edge) first:
    // [tucked fingertip x, knuckle x, centre y, half thickness, bow]. Upper fingers overlap the lower ones.
    $fingerSpecs = [[-18, 33, -18, 8.6, 2], [-23, 45, -36, 10, 3], [-24, 49, -55, 10.4, 3], [-21, 44, -74, 10, 3]];
    $fingers = collect($fingerSpecs)->map(function ($s) {
        [$x0, $x1, $y, $h, $bow] = $s;
        $m = ($x0 + $x1) / 2;
        $p = fn (...$v) => implode(' ', array_map(fn ($n) => round($n, 1), $v));

        return [
            'd' => 'M '.$p($x0, $y - 0.7 * $h)
                .' Q '.$p($m, $y - $h - $bow, $x1 - $h, $y - 0.96 * $h)
                .' C '.$p($x1 - 0.35 * $h, $y - 1.16 * $h, $x1 + 0.22 * $h, $y - 0.58 * $h, $x1 + 0.1 * $h, $y + 0.05 * $h)
                .' C '.$p($x1 + 0.02 * $h, $y + 0.6 * $h, $x1 - 0.45 * $h, $y + 0.98 * $h, $x1 - 1.15 * $h, $y + 0.92 * $h)
                .' Q '.$p($m, $y + $h - $bow, $x0, $y + 0.66 * $h)
                .' C '.$p($x0 - 0.6 * $h, $y + 0.6 * $h, $x0 - 0.6 * $h, $y - 0.62 * $h, $x0, $y - 0.7 * $h).' Z',
            'joint' => 'M '.$p($x0 + 0.3 * ($x1 - $x0), $y - 0.78 * $h).' q '.$p(2.6, 0.78 * $h, 0, 1.56 * $h),
            'joint2' => 'M '.$p($x0 + 0.3 * ($x1 - $x0) + 3, $y - 0.5 * $h).' q '.$p(1.6, 0.5 * $h, 0, $h),
            'knuckle' => 'M '.$p($x1 - 1.3 * $h, $y - 0.55 * $h).' q '.$p(2.6, 0.55 * $h, 0, 1.1 * $h)
                .' M '.$p($x1 - 0.9 * $h, $y - 0.4 * $h).' q '.$p(1.8, 0.4 * $h, 0, 0.8 * $h),
            'shine' => 'M '.$p($x0 + 5, $y - 0.52 * $h).' Q '.$p($m, $y - 0.6 * $h - $bow, $x1 - 1.4 * $h, $y - 0.6 * $h),
            'tip' => 'M '.$p($x0 + 1.5, $y + 0.64 * $h).' C '.$p($x0 - 0.5 * $h, $y + 0.56 * $h, $x0 - 0.5 * $h, $y - 0.58 * $h, $x0 + 1.5, $y - 0.68 * $h),
            'cap' => [$x1 - 0.3 * $h, $y - 0.45 * $h, 0.38 * $h, 0.22 * $h],
        ];
    });
    $palm = 'M -30 -90 C -10 -96, 20 -92, 36 -80 C 50 -66, 54 -30, 40 -10 C 28 2, -10 4, -30 -4 C -48 -12, -60 -32, -58 -52 C -56 -70, -46 -84, -30 -90 Z';
    // Cloth strips around the fist, clipped to the palm: one across the heel, two crossing over the fingers.
    $wraps = [
        'M -52 -100 C -56 -66, -60 -38, -56 0 L -28 8 C -32 -30, -30 -62, -22 -100 Z',
        'M -36 8 C -22 -32, -6 -66, 8 -102 L 27 -100 C 13 -64, -3 -30, -15 8 Z',
        'M -25 -102 C -17 -64, -7 -30, 1 10 L 22 8 C 12 -30, 4 -64, -4 -104 Z',
    ];
    $thumb = 'M -44 -84 C -34 -100, -14 -100, 2 -93 C 10 -89, 18 -84, 23 -79 C 30 -74, 30 -64, 22 -63 C 15 -62, 8 -66, 0 -70 C -10 -73, -20 -72, -30 -64';

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

        <radialGradient id="palmGrad" cx="0.3" cy="0.4" r="0.8">
            <stop offset="0" stop-color="#eeb084"/>
            <stop offset="0.55" stop-color="#d9966a"/>
            <stop offset="1" stop-color="#b57048"/>
        </radialGradient>
        <linearGradient id="fingerGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffd3ad"/>
            <stop offset="0.45" stop-color="#f0ae80"/>
            <stop offset="1" stop-color="#c27a4c"/>
        </linearGradient>
        <linearGradient id="thumbGrad" x1="0.2" y1="0" x2="0.4" y2="1">
            <stop offset="0" stop-color="#ffd9b8"/>
            <stop offset="0.5" stop-color="#f2b386"/>
            <stop offset="1" stop-color="#c98153"/>
        </linearGradient>
        <clipPath id="fistClip"><path d="{{ $palm }}"/></clipPath>
        <linearGradient id="clothGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#bfb9ac"/>
            <stop offset="0.25" stop-color="#f4f2ec"/>
            <stop offset="0.55" stop-color="#ffffff"/>
            <stop offset="0.85" stop-color="#e6e2d8"/>
            <stop offset="1" stop-color="#b3ad9f"/>
        </linearGradient>
        <linearGradient id="tailGrad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="0.6" stop-color="#efece4"/>
            <stop offset="1" stop-color="#c9c3b6"/>
        </linearGradient>
        <linearGradient id="wristGrad" x1="0.9" y1="0" x2="0.1" y2="1">
            <stop offset="0" stop-color="#f6be93"/>
            <stop offset="0.55" stop-color="#e2a072"/>
            <stop offset="1" stop-color="#b8714a"/>
        </linearGradient>
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
            <path d="M -80.5 -116.7 C -116.5 -137.7, -127.3 -166.3, -144.3 -276.4 L -211.7 -265.6 C -192.7 -155.7, -147.5 -84.3, -111.5 -63.3 Z" fill="url(#sleeveGrad)"/>
            <path d="M -94 -93.4 C -130 -114.4, -156 -161.6, -174 -271.6" fill="none" stroke="url(#foldGrad)" stroke-width="2.6"/>
            <path d="M -107 -71.1 C -143 -92.1, -181.8 -157.5, -199.8 -267.5" fill="none" stroke="url(#foldGrad)" stroke-width="2.2"/>
            <path d="M -87 -105.5 C -123 -126.5, -142.2 -163.9, -160.2 -273.9" fill="none" stroke="url(#foldLight)" stroke-width="2.4"/>
            <path d="M -101 -81.4 C -137 -102.4, -169.9 -159.4, -187.9 -269.4" fill="none" stroke="url(#foldLight)" stroke-width="1.8"/>
            <path d="M -24 -84 C -43.7 -89.3, -61.8 -97.3, -78.5 -107.8 L -102.5 -66.2 C -82.2 -55.4, -63.3 -42, -46 -26 Z" fill="url(#wristGrad)"/>
            <path d="M -24 -84 C -43.7 -89.3, -61.8 -97.3, -78.5 -107.8 M -102.5 -66.2 C -82.2 -55.4, -63.3 -42, -46 -26" fill="none" stroke="#7a4426" stroke-width="1.7"/>
            <path d="M -32 -83 C -48 -88, -62 -95, -76 -103" fill="none" stroke="#ffe2c8" stroke-opacity="0.5" stroke-width="3"/>
            <path d="M -98 -63 C -80 -54, -64 -42, -52 -32" fill="none" stroke="#8f4f2c" stroke-opacity="0.4" stroke-width="4"/>
            <path d="M -50 -89 Q -58 -70, -72 -51 M -56 -86 Q -63 -71, -73 -59" fill="none" stroke="#a8653d" stroke-opacity="0.45" stroke-width="1.2"/>

            <path d="{{ $palm }}" fill="url(#palmGrad)" stroke="#7a4426" stroke-width="1.8"/>
            <path d="M -50 -50 C -52 -34, -46 -20, -34 -12" fill="none" stroke="#fff" stroke-opacity="0.2" stroke-width="5"/>
            <path d="M -34 -14 C -40 -30, -40 -48, -36 -62" fill="none" stroke="#a8653d" stroke-opacity="0.5" stroke-width="1.3"/>

            <g transform="translate(-92 -88) rotate(-60)">
                <path d="M -33 -11 Q 0 -14 33 -11 L 33 11 Q 0 14 -33 11 Z" fill="url(#goldGrad)" stroke="#8a5a10" stroke-width="1.4"/>
                <path d="M -31 -6.5 Q 0 -9 31 -6.5 L 31 6.5 Q 0 9 -31 6.5 Z" fill="#7a1528" stroke="#560c1b" stroke-width="0.8"/>
                <g fill="none" stroke="#f2c14e" stroke-width="1.3">
                    @foreach ([-21, -7, 7, 21] as $cx)
                        <path d="M {{ $cx }} 2 c 0 -3.3 -2.4 -5.2 -5.2 -4.3 c -2.5 0.9 -2.3 4 0.2 4.4 M {{ $cx }} 2 c 0 -3.3 2.4 -5.2 5.2 -4.3 c 2.5 0.9 2.3 4 -0.2 4.4 M {{ $cx }} 2 v 3"/>
                    @endforeach
                </g>
                <path d="M -31 -9.5 Q 0 -12.5 31 -9.5" fill="none" stroke="#fff6d0" stroke-opacity="0.8" stroke-width="1.2"/>
            </g>

            @foreach ($fingers as $f)
                <path d="{{ $f['d'] }}" fill="#4a1f0a" fill-opacity="0.35" transform="translate(1.5 3.5)" filter="url(#fistBlur)"/>
                <path d="{{ $f['d'] }}" fill="url(#fingerGrad)" stroke="#83492a" stroke-width="1.4"/>
                <path d="{{ $f['tip'] }}" fill="none" stroke="#a8603a" stroke-opacity="0.5" stroke-width="4.5"/>
                <path d="{{ $f['shine'] }}" fill="none" stroke="#ffe6d0" stroke-opacity="0.6" stroke-width="2.4"/>
                <path d="{{ $f['joint'] }}" fill="none" stroke="#a8653d" stroke-opacity="0.85" stroke-width="1.3"/>
                <path d="{{ $f['joint2'] }}" fill="none" stroke="#a8653d" stroke-opacity="0.5" stroke-width="1"/>
                <path d="{{ $f['knuckle'] }}" fill="none" stroke="#a8653d" stroke-opacity="0.6" stroke-width="1.1"/>
                <ellipse cx="{{ $f['cap'][0] }}" cy="{{ $f['cap'][1] }}" rx="{{ $f['cap'][2] }}" ry="{{ $f['cap'][3] }}" fill="#fff" fill-opacity="0.35"/>
            @endforeach

            {{-- White cloth (ақ орамал) wound around the fist before striking the bone; the thumb stays free. --}}
            <g clip-path="url(#fistClip)">
                @foreach ($wraps as $d)
                    <path d="{{ $d }}" fill="#3a2a1a" fill-opacity="0.35" transform="translate(2 2.5)" filter="url(#fistBlur)"/>
                    <path d="{{ $d }}" fill="url(#clothGrad)" stroke="#a39d90" stroke-width="1"/>
                @endforeach
                <path d="M -40 -96 C -44 -62, -47 -34, -43 4 M -14 -100 C -6 -64, 3 -30, 12 9 M -26 8 C -13 -32, 3 -66, 17 -101" fill="none" stroke="#d6d1c6" stroke-width="1.1"/>
                <path d="M -34 -92 C -36 -70, -38 -50, -38 -34 M -8 -92 C -2 -70, 3 -50, 7 -34" fill="none" stroke="#fff" stroke-opacity="0.9" stroke-width="1.6"/>
            </g>

            <path d="{{ $thumb }} C -38 -58, -46 -62, -48 -70 C -50 -76, -48 -80, -44 -84 Z" fill="#4a1f0a" fill-opacity="0.35" transform="translate(2 4)" filter="url(#fistBlur)"/>
            <path d="{{ $thumb }} C -38 -58, -46 -62, -48 -70 C -50 -76, -48 -80, -44 -84 Z" fill="url(#thumbGrad)"/>
            <path d="{{ $thumb }}" fill="none" stroke="#7a4426" stroke-width="1.7"/>
            <path d="M -30 -64 C -38 -58, -46 -62, -48 -70" fill="none" stroke="#a8653d" stroke-opacity="0.5" stroke-width="1.2"/>
            <path d="M -30 -94 C -20 -97, -6 -95, 6 -88" fill="none" stroke="#ffe8d4" stroke-opacity="0.65" stroke-width="3"/>
            <path d="M 5 -91 q -2.5 5 -1.5 10" fill="none" stroke="#a8653d" stroke-width="1.3"/>
            <path d="M 1 -70 q 2.5 -3.5 6 -3" fill="none" stroke="#a8653d" stroke-opacity="0.6" stroke-width="1"/>
            <ellipse cx="16" cy="-78" rx="7" ry="4.6" transform="rotate(25 16 -78)" fill="url(#nailGrad)" stroke="#b07a58" stroke-width="1"/>
            <path d="M 10.5 -81 q -1.5 3.5 1 7" fill="none" stroke="#c98d6c" stroke-width="1"/>
            <ellipse cx="17" cy="-80" rx="3" ry="1.2" transform="rotate(25 17 -80)" fill="#fff" fill-opacity="0.8"/>

            <g>
                <animateTransform attributeName="transform" type="rotate" values="-6 -40 -88; 7 -40 -88; -6 -40 -88" dur="1.8s" repeatCount="indefinite"/>
                <path d="M -44 -88 C -54 -84, -60 -76, -70 -66 L -65 -64 L -63 -58 C -56 -70, -48 -78, -38 -86 Z" fill="url(#tailGrad)" stroke="#a39d90" stroke-width="1"/>
                <path d="M -45 -84 C -52 -80, -57 -74, -63 -66" fill="none" stroke="#d6d1c6" stroke-width="1"/>
            </g>
            <g>
                <animateTransform attributeName="transform" type="rotate" values="5 -40 -88; -6 -40 -88; 5 -40 -88" dur="1.5s" repeatCount="indefinite"/>
                <path d="M -42 -86 C -48 -78, -50 -70, -52 -58 L -48 -60 L -44 -56 C -44 -68, -40 -78, -36 -86 Z" fill="url(#tailGrad)" stroke="#a39d90" stroke-width="1"/>
            </g>
            <ellipse cx="-40" cy="-89" rx="7" ry="5.6" transform="rotate(-30 -40 -89)" fill="url(#tailGrad)" stroke="#a39d90" stroke-width="1"/>
            <path d="M -44 -91 q 4 2 8 -1" fill="none" stroke="#c9c3b6" stroke-width="1"/>
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
                        <rect x="-72" y="-38" width="4.5" height="34" rx="2.25" fill="url(#trailGrad)"/>
                        <rect x="-63" y="-28" width="3.5" height="24" rx="1.75" fill="url(#trailGrad)"/>
                        <rect x="60" y="-125" width="5" height="105" rx="2.5" fill="url(#trailGrad)"/>
                        <rect x="69" y="-95" width="3.5" height="68" rx="1.75" fill="url(#trailGrad)"/>
                    </g>
                    <use href="#fist"/>
                </g>
            </g>
        </g>
    </g>
</svg>
