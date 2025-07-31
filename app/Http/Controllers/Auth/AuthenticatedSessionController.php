<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use App\Http\Requests\LoginRequest;

class AuthenticatedSessionController extends Controller
{
     // ログインフォーム表示用
     public function showLoginForm(){
         return view('auth.login');
     }

    // ログイン処理
    public function store(LoginRequest $request){
        $request->authenticate(); // ここでログイン済み
        $request->session()->regenerate();
    
        return redirect()->intended(config('fortify.home'));
    }
    

    // ログアウト処理
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
