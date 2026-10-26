@extends('layouts.app')

@section('title', 'Жілік сындыру — әкімші')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="gate">
    <div class="zh-band"></div>
    <main class="gate-main">
        <h1 class="zh-title">Әкімші</h1>
        <p class="gate-sub">Админка · Жүргізуші кілттерін басқару</p>

        <form class="gate-card" method="POST" action="/admin/login">
            @csrf
            @if ($enabled)
                <input class="name-input" type="password" name="password" placeholder="Құпиясөз · Пароль" autocomplete="current-password" autofocus required>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
                <button class="zh-btn" type="submit">Кіру · Войти</button>
            @else
                <p class="form-error">ZHILIK_ADMIN_PASSWORD орнатылмаған · Пароль админки не задан в .env</p>
            @endif
        </form>
    </main>
    <div class="zh-band"></div>
</div>
@endsection
