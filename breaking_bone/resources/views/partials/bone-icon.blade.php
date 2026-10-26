<svg class="{{ $class ?? '' }}" viewBox="0 30 600 160" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $id }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fffdf5"/>
            <stop offset="0.45" stop-color="#f4e7ca"/>
            <stop offset="1" stop-color="#c2a674"/>
        </linearGradient>
    </defs>
    <path d="M 120 88 C 220 96, 380 96, 480 88 C 500 72, 510 46, 542 46 C 580 46, 596 76, 576 98 C 568 105, 568 115, 576 122 C 596 144, 580 174, 542 174 C 510 174, 500 148, 480 132 C 380 124, 220 124, 120 132 C 100 148, 90 174, 58 174 C 20 174, 4 144, 24 122 C 32 115, 32 105, 24 98 C 4 76, 20 46, 58 46 C 90 46, 100 72, 120 88 Z"
          fill="url(#{{ $id }})" stroke="#6b5530" stroke-width="6" stroke-linejoin="round"/>
    <path d="M 300 94 L 294 104 L 306 112 L 297 126" fill="none" stroke="#3f2b10" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
