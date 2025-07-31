<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('admin.login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();
    
        if (!Auth::user()->is_admin) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => '管理者アカウントではありません。',
            ]);
        }
    
        $request->session()->regenerate();
        return redirect()->intended('/admin/attendance/list');
    }
    

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
