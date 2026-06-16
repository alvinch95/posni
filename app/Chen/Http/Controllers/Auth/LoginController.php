<?php

namespace App\Chen\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::guard('chen')->check()) {
            return redirect()->route('chen.home');
        }

        return view('chen::auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('chen')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        // route('chen.home') yields the bare domain (no trailing slash) for the "/" route;
        // normalise to ".../" so the post-login landing URL is the canonical home path.
        return redirect()->intended(rtrim(route('chen.home'), '/') . '/');
    }

    public function logout(Request $request)
    {
        Auth::guard('chen')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('chen.login');
    }
}
