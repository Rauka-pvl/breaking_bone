@extends('layouts.app')

@section('title', 'Жілік сындыру — жүргізуші')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="gate">
    <div class="zh-band"></div>
    <main class="gate-main">
        <h1 class="zh-title">Жілік сындыру</h1>
        <p class="gate-sub">Жүргізуші кілтін енгізіңіз · Введите ключ ведущего</p>

        <form class="gate-card" method="POST" action="/">
            @csrf
            @include('partials.bone-icon', ['id' => 'boneGate', 'class' => 'mini-bone'])
            <input class="name-input" name="key" value="{{ old('key') }}" placeholder="XXXX-XXXX" autocomplete="off" autocapitalize="characters" autofocus required maxlength="40">
            @error('key')
                <p class="form-error">{{ $message }}</p>
            @enderror
            <button class="zh-btn" type="submit">Кіру · Войти</button>
            <p class="gate-hint">Кілтті әкімшіден алыңыз · Ключ выдаёт администратор</p>
        </form>
    </main>
    <div class="zh-band"></div>
</div>
@endsection
