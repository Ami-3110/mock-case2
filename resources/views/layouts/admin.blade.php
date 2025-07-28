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
            <a href="{{ route('admin.attendance.list') }}" class="logo-image">
                <img src="{{ asset('images/logo.svg') }}" alt="ロゴ" class="logo-png">
            </a>
        </div>

            {{-- 右：リンクセット --}}
        <div class="header-right">
            <div class="link-set">
                <a href="{{ route('admin.attendance.list') }}" class="attendance-status_Link">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}" class="attendance-all__link">スタッフ一覧</a>
                <a href="{{ route('stamp_correction_request.list') }}" class="atpplication-form__link">申請一覧</a>
                @auth
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('admin.login') }}" class="login-btn">ログイン</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="main-container">
        @yield('content')
    </main>
</body>
</html>