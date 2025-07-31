@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')

<div class="auth-form">
    <h2 class="title">会員登録</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label class="label" for="name">名前</label>
        <input class="form" type="text" name="name" id="name" value="{{ old('name') }}">
        @error('name')
        <div class="error">{{ $message }}</div>
        @enderror

        <label class="label" for="email">メールアドレス</label>
        <input class="form" type="email" name="email" id="email" value="{{ old('email') }}">
        @error('email')
        <div class="error">{{ $message }}</div>
        @enderror

        <label class="label" for="password">パスワード</label>
        <input class="form" type="password" name="password" id="password">
        @error('password')
        <div class="error">{{ $message }}</div>
        @enderror

        <label class="label" for="password_confirmation">パスワード確認</label>
        <input class="form" type="password" name="password_confirmation" id="password_confirmation">

        <button class="button" type="submit">登録する</button>
    </form>

    <p class="auth-link">
        <a href="{{ route('login') }}">ログインはこちら</a>
    </p>
</div>
@endsection