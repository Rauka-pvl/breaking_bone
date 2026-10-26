<?php

namespace App\Http\Controllers;

use App\Models\HostKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const SESSION_KEY = 'zhilik_admin';

    public function index(Request $request): View
    {
        if (! $this->isAdmin($request)) {
            return view('admin.login', ['enabled' => $this->password() !== '']);
        }

        $keys = HostKey::query()
            ->withCount('games')
            ->latest('id')
            ->get();

        return view('admin.keys', ['keys' => $keys]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'max:200']]);
        $password = $this->password();

        if ($password === '' || ! hash_equals($password, $data['password'])) {
            return redirect('/admin')->withErrors(['password' => 'Құпиясөз қате · Неверный пароль']);
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);

        return redirect('/admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect('/admin');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'key' => ['nullable', 'string', 'min:4', 'max:40', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);

        $key = filled($data['key'] ?? null) ? strtoupper($data['key']) : HostKey::generateKey();

        validator(['key' => $key], ['key' => [Rule::unique('host_keys', 'key')]], [
            'key.unique' => 'Бұл кілт бар · Такой ключ уже существует',
        ])->validate();

        $hostKey = HostKey::query()->create([
            'name' => trim(strip_tags($data['name'])),
            'key' => $key,
            'code' => HostKey::generateCode(),
            'active' => true,
        ]);

        return redirect('/admin')->with('created', $hostKey->id);
    }

    public function toggle(Request $request, HostKey $hostKey): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $hostKey->update(['active' => ! $hostKey->active]);

        return redirect('/admin');
    }

    public function destroy(Request $request, HostKey $hostKey): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $hostKey->delete();

        return redirect('/admin');
    }

    private function isAdmin(Request $request): bool
    {
        return $this->password() !== '' && $request->session()->get(self::SESSION_KEY) === true;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request), 403);
    }

    private function password(): string
    {
        return (string) config('zhilik.admin_password');
    }
}
