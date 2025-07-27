@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="auth-form">
    <h2 class="title">管理者ログイン</h2>
    <form method="POST" action="{{ route('admin.login') }}">
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

        <button class="button" type="submit">管理者ログインする</button>
    </form>
</div>
@endsection
