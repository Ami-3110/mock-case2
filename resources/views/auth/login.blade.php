@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="auth-form">
    <h2 class="title">ログイン</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label class="label" for="email">メールアドレス</label>
        <input class="form" type="email" name="email" id="email">
        @error('email')
        <div class="error">{{ $message }}</div>
        @enderror

        <label class="label" for="password">パスワード</label>
        <input class="form" type="password" name="password" id="password">
        @error('password')
        <div class="error">{{ $message }}</div>
        @enderror

        <button class="button" type="submit">ログインする</button>
    </form>
    <p class="auth-link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </p>
</div>
@endsection
