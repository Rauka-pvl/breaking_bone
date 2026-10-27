@php($target = config('zhilik.target_taps'))

@extends('layouts.app', [
    'entry' => 'resources/js/play.js',
    'zhilik' => $config,
])

@section('title', 'Жілік сындыру')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="play" id="play" data-view="join">
    <header class="team-bar">
        <h1>Жілік сындыру</h1>
        <p>Бірінші {{ $target }} рет бас · Первым нажми {{ $target }} раз</p>
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
        <p style="font-size:.9rem">Ойын басталғанда батырманы {{ $target }} рет бірінші болып басыңыз!<br>Когда игра начнётся — первым нажмите кнопку {{ $target }} раз!</p>
    </section>

    <section class="view view-countdown">
        <p>Дайындал! · Приготовься!</p>
        <div class="big-num" id="phoneCount">3</div>
    </section>

    <section class="view view-tap">
        <div class="tap-meta"><span id="leaderInfo"></span></div>
        <button class="tap-btn" id="tapBtn" type="button">
            @include('partials.bone-icon', ['id' => 'boneTap'])
            <span>Бас!</span>
        </button>
        <div class="my-taps"><span id="myTaps">0</span><em>/ {{ $target }}</em><small>менің басуым · мои нажатия</small></div>
        <div class="my-progress"><i id="myProgress"></i></div>
    </section>

    <section class="view view-done">
        <div class="result-title win">{{ $target }}!</div>
        <p>Тамаша! · Отлично!</p>
        <p>Сіздің уақытыңыз · Ваше время: <b id="doneTime"></b></p>
        <p class="waiting-dots">Нәтижені күтіңіз · Ждём результат</p>
    </section>

    <section class="view view-judging">
        @include('partials.bone-icon', ['id' => 'boneJudge', 'class' => 'mini-bone'])
        <h2 class="waiting-dots">Ойын аяқталды</h2>
        <p>Игра окончена · Экранға қараңыз!</p>
        <p>Сіз <b id="judgeTaps">0</b> рет бастыңыз</p>
    </section>

    <section class="view view-result" id="resultView">
        @include('partials.bone-break')
        <div class="winner-card" id="winnerCard">
            <div class="crown">👑</div>
            <div class="label" id="winnerLabel">Жеңімпаз · Победитель</div>
            <div class="name" id="resultWinner"></div>
            <div class="meta" id="resultWinnerMeta"></div>
            <ol class="top3" id="resultTop"></ol>
        </div>
        <div class="after">
            <div class="result-title" id="resultTitle"></div>
            <p id="resultSub"></p>
            <div class="my-taps"><span id="resultTaps">0</span><em>/ {{ $target }}</em><small id="resultTime">менің басуым · мои нажатия</small></div>
            <p class="waiting-dots" style="font-size:.9rem">Келесі ойынды күтіңіз · Ждите следующую игру</p>
        </div>
    </section>

    <section class="view view-full">
        <h2>Орын жоқ</h2>
        <p>Ойында {{ config('zhilik.max_players') }} ойыншы бар.<br>В игре уже {{ config('zhilik.max_players') }} игроков.</p>
        <button class="zh-btn ghost" id="retryBtn" type="button">Қайталау · Повторить</button>
    </section>
</div>
@endsection
