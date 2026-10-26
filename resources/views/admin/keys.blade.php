@extends('layouts.app')

@section('title', 'Жілік сындыру — кілттер')
@section('bodyClass', 'zh-bg')

@section('content')
<div class="admin">
    <header class="admin-header">
        <h1 class="zh-title">Кілттер · Ключи</h1>
        <form method="POST" action="/admin/logout">
            @csrf
            <button class="zh-btn ghost small" type="submit">Шығу · Выйти</button>
        </form>
    </header>

    <section class="admin-card">
        <h2>Жаңа кілт · Новый ключ</h2>
        <form class="admin-form" method="POST" action="/admin/keys">
            @csrf
            <label>
                <span>Атауы · Название (место, ведущий)</span>
                <input name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Мысалы: Алматы, той залы">
            </label>
            <label>
                <span>Кілт · Ключ (бос қалса — автоматты)</span>
                <input name="key" value="{{ old('key') }}" maxlength="40" placeholder="Авто: XXXX-XXXX" autocomplete="off">
            </label>
            <button class="zh-btn small" type="submit">Қосу · Добавить</button>
        </form>
        @if ($errors->any())
            <p class="form-error">{{ $errors->first() }}</p>
        @endif
    </section>

    <section class="admin-card">
        <h2>Барлығы · Всего: {{ $keys->count() }}</h2>

        @if ($keys->isEmpty())
            <p class="muted">Кілттер әлі жоқ · Ключей пока нет</p>
        @else
            <div class="table-wrap">
                <table class="keys-table">
                    <thead>
                        <tr>
                            <th>Атауы · Название</th>
                            <th>Кілт · Ключ</th>
                            <th>Ссылка для ведущего</th>
                            <th>Игр</th>
                            <th>Последний старт</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($keys as $hostKey)
                            @php $hostUrl = url('/?key='.urlencode($hostKey->key)); @endphp
                            <tr @class(['inactive' => ! $hostKey->active, 'fresh' => session('created') === $hostKey->id])>
                                <td>{{ $hostKey->name }}</td>
                                <td><code class="key">{{ $hostKey->key }}</code></td>
                                <td>
                                    <button class="link-btn" type="button" data-copy="{{ $hostUrl }}">Көшіру · Копировать</button>
                                </td>
                                <td>{{ $hostKey->games_count }}</td>
                                <td>{{ $hostKey->last_used_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="/admin/keys/{{ $hostKey->id }}/toggle">
                                        @csrf
                                        @method('PATCH')
                                        <button class="status-btn {{ $hostKey->active ? 'on' : 'off' }}" type="submit">
                                            {{ $hostKey->active ? 'Қосулы · Вкл' : 'Өшірулі · Выкл' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="/admin/keys/{{ $hostKey->id }}" onsubmit="return confirm('Кілтті өшіру? Удалить ключ «{{ e($hostKey->name) }}» и все его игры?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-btn" type="submit">Жою · Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script>
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = btn.dataset.copy;
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                window.prompt('Скопируйте ссылку:', text);
                return;
            }
            const label = btn.textContent;
            btn.textContent = 'Көшірілді ✓';
            setTimeout(() => { btn.textContent = label; }, 1500);
        });
    });
</script>
@endsection
