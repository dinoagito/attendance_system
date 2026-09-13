<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginValue = trim($credentials['login']);
        $user = str_contains($loginValue, '@')
            ? User::where('email', $loginValue)->first()
            : User::whereRaw('LOWER(name) = ?', [mb_strtolower($loginValue)])->first();

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['login' => 'The provided credentials are incorrect.'])
            ->onlyInput('login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $slug = Str::slug($data['name']);
        $uniqueEmail = sprintf('%s-%s@local.test', $slug !== '' ? $slug : 'admin', now()->format('YmdHis'));

        $user = User::create([
            'name' => $data['name'],
            'email' => $uniqueEmail,
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}