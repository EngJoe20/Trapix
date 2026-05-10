<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Quick login for Eng. Mina (Dev Only)
     */
    public function loginAsMina(Request $request): RedirectResponse
    {
        $user = \App\Models\User::where('email', 'eng.mina@trapix.com')->first();
        
        if ($user) {
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->route('login')->with('status', 'Eng. Mina user not found. Please run the seeder.');
    }
}
