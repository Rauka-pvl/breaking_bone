@extends('layouts.app', [
    'entry' => 'resources/js/screen.js',
    'zhilik' => $config + ['joinUrl' => $joinUrl],
])

@section('title', 'Жілік сындыру — экран')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="screen" id="screen">
    <div class="zh-band"></div>

    <header class="screen-header">
        <h1 class="zh-title">Жілік сындыру</h1>
        <p>Қазақтың ұлттық дәстүрі · Казахская национальная традиция · Кім бірінші {{ config('zhilik.target_taps') }} рет басады?</p>
    </header>

    <main class="screen-main">
        <aside class="panel join-panel" id="joinPanel">
            <h2>Қосыл!</h2>
            <div class="sub">Сканируй QR и играй</div>
            <div class="qr" id="qr"><img alt="QR"></div>
            <div class="count"><b id="playerCount">0</b> / <span id="maxPlayers">{{ config('zhilik.max_players') }}</span> ойыншы</div>
            <ul class="names" id="names"></ul>
        </aside>

        <section class="stage" id="stage">
            <canvas class="fx" id="fx"></canvas>

            <div class="timer" id="timer">0.0</div>
            <div class="status-line" id="status">Ойыншыларды күтеміз · Ждём игроков</div>

            <div class="bone-wrap" id="bone">
                @include('partials.bone')
            </div>

            <div class="race" aria-hidden="true">
                <div class="race-bar"><div class="fill" id="raceFill"></div></div>
                <div class="race-label" id="raceLabel">Бірінші {{ config('zhilik.target_taps') }} басқан жеңеді · Первый до {{ config('zhilik.target_taps') }} побеждает</div>
            </div>

            <div class="controls" id="controls">
                <button class="zh-btn" id="startBtn">Бастау · Старт</button>
                <button class="zh-btn ghost" id="resetBtn">Жаңа ойын</button>
            </div>

            <div class="overlay" id="countdown"><div class="countdown-num" id="countNum">3</div></div>

            <div class="overlay result" id="result">
                <div class="result-card">
                    <h2 class="zh-title">Жілік сынды!</h2>
                    <div class="winner-name" id="winnerName"></div>
                    <div class="final" id="winnerTime"></div>
                    <ol class="podium" id="podium"></ol>
                </div>
            </div>
        </section>

        <aside class="panel board-panel">
            <h2>Көшбасшылар</h2>
            <div class="sub">Лидеры · {{ config('zhilik.target_taps') }} басу</div>
            <ol class="board" id="board"></ol>
        </aside>
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
