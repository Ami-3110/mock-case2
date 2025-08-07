@extends('layouts.admin')
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-list.css') }}">
@endsection
@section('content')
<div class="container">
    <nav class="title">{{ $date->format('Y年n月j日') }}の勤怠</nav>

    <div class="day-navigation">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}">←前日</a>
        
        <span class="day-center">
            <label for="day-select" class="calendar-trigger">
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar">
            </label>
            {{ $date->format('Y/m/d') }}
            <input type="date" id="day-select" value="{{ $date->format('Y-m-d') }}">
        </span>
        
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}">翌日→</a>
    </div>

    <!-- 隠しGETフォーム：ここに date を入れて投げる -->
    <form id="day-form" action="{{ route('admin.attendance.list') }}" method="GET" style="display:none;">
        <input type="hidden" name="date" id="df-date" value="{{ $date->format('Y-m-d') }}">
    </form>

    <table class="attendance-today">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '未打刻' }}</td>
                    <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '未打刻' }}</td>
                    <td>{{ $attendance->break_time }}</td>
                    <td>{{ $attendance->work_time }}</td>
                    <td><a href="{{ route('admin.attendances.showFixForm', $attendance->id) }}" class="attendance-detail">詳細</a></td>
                </tr>

            @empty
                <tr>
                    <td colspan="6">この日の勤怠データはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input   = document.getElementById('day-select');
        const trigger = document.querySelector('.calendar-trigger');

        if (!input || !trigger) return;

        // アイコンクリックで date ピッカーを開く
        trigger.addEventListener('click', function (e) {
        e.preventDefault();
        if (input.showPicker) { input.showPicker(); }
        else { input.focus(); input.click(); }
        });

        // 選んだら /admin/attendance/list/YYYY-MM-DD に遷移
        const base = @json(url('/admin/attendance/list'));  // ← ベースURLをBladeから埋め込む
        input.addEventListener('change', function () {
        const ymd = this.value; // "YYYY-MM-DD"
        if (!ymd) return;
        window.location.href = `${base}/${ymd}`;
        });
    });
</script>
      
@endsection
