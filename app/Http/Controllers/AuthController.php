<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginScreen(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('operator_authenticated')) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $limiterKey = 'operator-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            abort(429, 'Too many sign-in attempts. Please wait a minute and try again.');
        }

        $validated = $request->validate(['key' => 'required|string']);
        $operatorKey = (string) config('register.operator_key');

        if ($operatorKey === '' || ! hash_equals($operatorKey, $validated['key'])) {
            RateLimiter::hit($limiterKey, 60);
            Log::warning('Rejected operator key on sign-in', ['ip' => $request->ip()]);

            return back()->withErrors(['key' => 'That key is not right.']);
        }

        RateLimiter::clear($limiterKey);
        $request->session()->regenerate();
        $request->session()->put('operator_authenticated', true);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('operator_authenticated');
        $request->session()->regenerate();

        return redirect('/login');
    }
}
