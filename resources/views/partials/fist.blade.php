@php
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
            'shine' => 'M '.$p($x0 + 5, $y - 0.52 * $h).' Q '.$p($m, $y - 0.6 * $h - $bow, $x1 - 1.4 * $h, $y - 0.6 * $h),
            'tip' => 'M '.$p($x0 + 1.5, $y + 0.64 * $h).' C '.$p($x0 - 0.5 * $h, $y + 0.56 * $h, $x0 - 0.5 * $h, $y - 0.58 * $h, $x0 + 1.5, $y - 0.68 * $h),
        ];
    });
    $palm = 'M -30 -90 C -10 -96, 20 -92, 36 -80 C 50 -66, 54 -30, 40 -10 C 28 2, -10 4, -30 -4 C -48 -12, -60 -32, -58 -52 C -56 -70, -46 -84, -30 -90 Z';
    // Edges of the cloth turns wound over the whole fist: a steep spiral plus a fainter crossing layer.
    $wrapTurns = collect(range(-4, 4))->map(fn ($k) => sprintf('M %d 14 Q %d -48 %d -110', $k * 21 - 24, $k * 21 + 10, $k * 21 + 22));
    $wrapCross = collect(range(-2, 3))->map(fn ($k) => sprintf('M %d 14 Q %d -48 %d -110', $k * 30 + 26, $k * 30 + 8, $k * 30 - 20));
    $thumb = 'M -44 -84 C -34 -100, -14 -100, 2 -93 C 10 -89, 18 -84, 23 -79 C 30 -74, 30 -64, 22 -63 C 15 -62, 8 -66, 0 -70 C -10 -73, -20 -72, -30 -64';
@endphp
    <linearGradient id="thumbGrad" x1="0.2" y1="0" x2="0.4" y2="1">
        <stop offset="0" stop-color="#ffd9b8"/>
        <stop offset="0.5" stop-color="#f2b386"/>
        <stop offset="1" stop-color="#c98153"/>
    </linearGradient>
    <clipPath id="fistClip">
        <path d="{{ $palm }}"/>
        @foreach ($fingers as $f)
            <path d="{{ $f['d'] }}"/>
        @endforeach
    </clipPath>
    <radialGradient id="clothPalm" cx="0.35" cy="0.35" r="0.8">
        <stop offset="0" stop-color="#ffffff"/>
        <stop offset="0.6" stop-color="#ece8df"/>
        <stop offset="1" stop-color="#bfb8a8"/>
    </radialGradient>
    <linearGradient id="clothFinger" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#ffffff"/>
        <stop offset="0.5" stop-color="#f2efe8"/>
        <stop offset="1" stop-color="#c8c1b2"/>
    </linearGradient>
    <radialGradient id="clothShade" cx="0.42" cy="0.38" r="0.7">
        <stop offset="0" stop-color="#6b604c" stop-opacity="0"/>
        <stop offset="0.7" stop-color="#6b604c" stop-opacity="0"/>
        <stop offset="1" stop-color="#6b604c" stop-opacity="0.35"/>
    </radialGradient>
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

        {{-- White cloth (ақ орамал) wound over the whole fist and the wrist before striking; the thumb stays free. --}}
        <path d="{{ $palm }}" fill="url(#clothPalm)" stroke="#8f8878" stroke-width="1.6"/>
        <g transform="translate(-61.75 -70.5) rotate(-60)">
            <path d="M -30 -9 Q 0 -12 30 -9 Q 33 0 30 9 Q 0 12 -30 9 Q -33 0 -30 -9 Z" fill="url(#clothGrad)" stroke="#a39d90" stroke-width="1"/>
            <path d="M -31 1 Q 0 -2 31 -4" fill="none" stroke="#a39d90" stroke-width="1"/>
            <path d="M -24 -4 Q 0 -6.5 24 -6" fill="none" stroke="#fff" stroke-opacity="0.9" stroke-width="1.6"/>
        </g>

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
            <path d="{{ $f['d'] }}" fill="#3a2a1a" fill-opacity="0.28" transform="translate(1.5 3.5)" filter="url(#fistBlur)"/>
            <path d="{{ $f['d'] }}" fill="url(#clothFinger)" stroke="#8f8878" stroke-width="1.3"/>
            <path d="{{ $f['tip'] }}" fill="none" stroke="#b3ac9c" stroke-opacity="0.6" stroke-width="4"/>
            <path d="{{ $f['shine'] }}" fill="none" stroke="#fff" stroke-opacity="0.9" stroke-width="2.4"/>
        @endforeach

        <g clip-path="url(#fistClip)" fill="none">
            @foreach ($wrapCross as $d)
                <path d="{{ $d }}" stroke="#b9b3a5" stroke-opacity="0.55" stroke-width="1"/>
            @endforeach
            @foreach ($wrapTurns as $d)
                <path d="{{ $d }}" stroke="#fff" stroke-opacity="0.85" stroke-width="2.4" transform="translate(3 0)"/>
                <path d="{{ $d }}" stroke="#9c9586" stroke-opacity="0.75" stroke-width="1.1"/>
            @endforeach
            <rect x="-70" y="-110" width="130" height="126" fill="url(#clothShade)"/>
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
