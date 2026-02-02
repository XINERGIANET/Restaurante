<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('pages.auth.signin', ['title' => 'Login']);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            $branchId = $user?->person?->branch_id;
            $request->session()->put('branch_id', $branchId);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'name' => 'Credenciales invalidas.',
        ])->onlyInput('name');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesion cerrada.');
    }
}
