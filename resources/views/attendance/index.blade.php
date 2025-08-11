@extends('layouts.user')
@section('css')
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
@endsection
@section('content')
<div class="container">
    <div class="status-box">
        <p>{{ $status }}</p>
        <p><span class="current-date" id="current-date">{{ $date }}</span></p>
        <p><span class="current-time time" id="current-time">{{ $time }}</span></p>
    </div>

    @if ($status === '勤務外')
        <form method="POST" action="{{ route('attendance.start') }}">
            @csrf
            <button class="action-work"type="submit">出勤</button>
        </form>
    @elseif ($status === '出勤中')
        <div class="button-group">
            <form method="POST" action="{{ route('attendance.end') }}">
                @csrf
                <button class="action-work" type="submit">退勤</button>
            </form>
            <form method="POST" action="{{ route('break.start') }}">
                @csrf
                <button class="action-break" type="submit">休憩入り</button>
            </form>
        </div>
    @elseif ($status === '休憩中')
        <form method="POST" action="{{ route('break.end') }}">
            @csrf
            <button class="action-break" type="submit">休憩戻り</button>
        </form>
    @elseif ($status === '退勤済')
        <p class="goodbye-message">お疲れ様でした。</p>
    @endif
</div>

<script>
// 時刻のライブ更新
  const days = ['日', '月', '火', '水', '木', '金', '土'];

  function updateDateTime() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1; // 0始まりなので +1
    const date = now.getDate();
    const day = days[now.getDay()]; // 曜日を日本語に変換

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    document.getElementById('current-date').textContent = `${year}年${month}月${date}日（${day}）`;
    document.getElementById('current-time').textContent = `${hours}:${minutes}`;
  }

  updateDateTime();
  setInterval(updateDateTime, 1000);
</script>
@endsection
