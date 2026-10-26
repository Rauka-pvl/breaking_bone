@php
    $teamNames = ['left' => ['Көк жақ', 'Синие'], 'right' => ['Қызыл жақ', 'Красные']];
    [$kk, $ru] = $teamNames[$side];
@endphp

@extends('layouts.app', [
    'entry' => 'resources/js/play.js',
    'zhilik' => $config + ['side' => $side],
])

@section('title', "Жілік сындыру — {$kk}")
@section('bodyClass', 'zh-bg')

@section('content')
<div class="play side-{{ $side }}" id="play" data-view="join">
    <header class="team-bar">
        <h1>{{ $kk }}</h1>
        <p>{{ $ru }} · Жілік сындыру</p>
    </header>

    <section class="view view-join">
        @include('partials.bone-icon', ['id' => 'boneJoin', 'class' => 'mini-bone'])
        <h2>Ойынға қосылу</h2>
        <p>Есіміңізді жазыңыз · Введите имя</p>
        <form id="joinForm" style="display:contents">
            <input class="name-input" id="nameInput" maxlength="40" placeholder="Есімі · Имя" autocomplete="nickname">
            <button class="zh-btn" type="submit" id="joinBtn">Қосылу · Войти</button>
        </form>
        <p class="error" id="joinError" style="color:#ffb4b4"></p>
    </section>

    <section class="view view-wait">
        @include('partials.bone-icon', ['id' => 'boneWait', 'class' => 'mini-bone'])
        <h2 id="helloName">Сәлем!</h2>
        <p class="waiting-dots">Ойын басталуын күтіңіз · Ждите начала игры</p>
        <p id="waitCounts"></p>
        <p style="font-size:.9rem">Ойын басталғанда 10 секунд ішінде батырманы мүмкіндігінше көп басыңыз!<br>Когда игра начнётся — жмите кнопку как можно чаще 10 секунд!</p>
    </section>

    <section class="view view-countdown">
        <p>Дайындал! · Приготовься!</p>
        <div class="big-num" id="phoneCount">3</div>
    </section>

    <section class="view view-tap">
        <div class="tap-meta"><span id="tapTeam">{{ $kk }}</span><span class="time" id="tapTime">10.0</span></div>
        <button class="tap-btn" id="tapBtn" type="button">
            @include('partials.bone-icon', ['id' => 'boneTap'])
            <span>Бас!</span>
        </button>
        <div class="my-taps"><span id="myTaps">0</span><small>менің басуым · мои нажатия</small></div>
        <div class="team-mini">
            <div class="tug"><div class="fill" id="miniFill"></div><div class="mid"></div></div>
            <div class="nums"><span id="miniLeft" style="color:#7fb0ff">0</span><span id="miniRight" style="color:#ff8a8c">0</span></div>
        </div>
    </section>

    <section class="view view-judging">
        @include('partials.bone-icon', ['id' => 'boneJudge', 'class' => 'mini-bone'])
        <h2 class="waiting-dots">Санап жатырмыз</h2>
        <p>Считаем результаты · Экранға қараңыз!</p>
        <p>Сіз <b id="judgeTaps">0</b> рет бастыңыз</p>
    </section>

    <section class="view view-result">
        <div class="result-title" id="resultTitle"></div>
        <p id="resultSub"></p>
        <div class="my-taps"><span id="resultTaps">0</span><small>менің басуым · мои нажатия</small></div>
        <p id="resultScore" style="font-weight:800;font-size:1.3rem"></p>
        <p class="waiting-dots" style="font-size:.9rem">Келесі ойынды күтіңіз · Ждите следующую игру</p>
    </section>

    <section class="view view-full">
        <h2>Орын жоқ</h2>
        <p>Бұл жақта {{ config('zhilik.max_per_side') }} ойыншы бар.<br>На этой стороне уже {{ config('zhilik.max_per_side') }} игроков.</p>
        <button class="zh-btn ghost" id="retryBtn" type="button">Қайталау · Повторить</button>
    </section>
</div>
@endsection
