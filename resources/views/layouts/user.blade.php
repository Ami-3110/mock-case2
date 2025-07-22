<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
    <title>{{ config('app.name', 'coachtech勤怠管理アプリ') }}</title>

</head>
<body class="body">

    <header class="header">
        <div class="function-bar">

        {{-- 左：ロゴ --}}
        <div class="header-left">
            <a href="{{ url('/') }}" class="logo-image">
                <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" class="logo-png">
            </a>
        </div>

            {{-- 右：リンクセット --}}
        <div ckass="header-right">
            <dic class="link-set">
                <a href="{{ route('attendance.index') }}" class="attendance-status_Link">勤怠</a>
                <a href="{{ route('attendance.index') }}" class="attendance-all__link">勤怠一覧</a>
                <a href="{{ route('attendance.fix') }}" class="atpplocation-form__link">申請</a>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="login-btn">ログイン</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="main-container">
        @yield('content')
    </main>
    @yield('js')
</body>
</html>
