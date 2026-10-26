@extends('layouts.app', [
    'entry' => 'resources/js/screen.js',
    'zhilik' => $config + ['joinUrls' => $joinUrls],
])

@section('title', 'Жілік сындыру — экран')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="screen" id="screen">
    <div class="zh-band"></div>

    <header class="screen-header">
        <h1 class="zh-title">Жілік сындыру</h1>
        <p>Қазақтың ұлттық дәстүрі · Казахская национальная традиция · QR-ды сканерлеп, жағыңды таңда!</p>
    </header>

    <main class="screen-main">
        @foreach (['left' => ['Көк жақ', 'Синие'], 'right' => ['Қызыл жақ', 'Красные']] as $side => [$kk, $ru])
            @if ($side === 'right')
                <section class="stage" id="stage">
                    <canvas class="fx" id="fx"></canvas>

                    <div class="timer" id="timer">10.0</div>
                    <div class="status-line" id="status">Ойыншыларды күтеміз · Ждём игроков</div>

                    <div class="bone-wrap" id="bone">
                        @include('partials.bone')
                    </div>

                    <div class="tug" aria-hidden="true"><div class="fill" id="tugFill"></div><div class="mid"></div></div>

                    <div class="controls" id="controls">
                        <button class="zh-btn" id="startBtn">Бастау · Старт</button>
                        <button class="zh-btn ghost" id="resetBtn">Жаңа ойын</button>
                    </div>

                    <div class="overlay" id="countdown"><div class="countdown-num" id="countNum">3</div></div>

                    <div class="overlay result" id="result">
                        <div class="result-card">
                            <h2 class="zh-title">Жілік сынды!</h2>
                            <div class="winner-name" id="winnerName"></div>
                            <div class="final" id="finalScore"></div>
                            <div class="top" id="topPlayers"></div>
                        </div>
                    </div>
                </section>
            @endif

            <aside class="team {{ $side }}" id="team-{{ $side }}">
                <h2>{{ $kk }}</h2>
                <div class="sub">{{ $ru }}</div>
                <div class="qr" id="qr-{{ $side }}"><img alt="QR {{ $kk }}"></div>
                <div class="count"><b data-count>0</b> / <span data-max>{{ config('zhilik.max_per_side') }}</span> ойыншы</div>
                <div class="score" data-score>0</div>
                <div class="score-label">басу · нажатий</div>
                <ul class="names" data-names></ul>
            </aside>
        @endforeach
    </main>

    <div class="zh-band"></div>
</div>

<div class="flash" id="flash"></div>
<div class="room-badge">
    <span>{{ $room->name }}</span>
    <form method="POST" action="/logout" onsubmit="return confirm('Шығу? Выйти из комнаты?')">
        @csrf
        <button type="submit">Шығу</button>
    </form>
</div>
<div class="conn" id="conn"><i></i><span>Reverb</span></div>
@endsection
