@extends('layouts.user')

@php
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

@section('content')
<div class="container">
    <h2>勤怠一覧</h2>

    <div class="month-navigation">
        <a href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">
            &lt; {{ $prevMonth->format('Y年n月') }}
        </a>
        <span>{{ $year }}年{{ $month }}月</span>
        <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">
            {{ $nextMonth->format('Y年n月') }} &gt;
        </a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $attendance)                
                <tr>
                    <td>
                        {{ \Carbon\Carbon::parse($attendance->work_date)->format('m/d') }}
                        ({{ $weekdays[\Carbon\Carbon::parse($attendance->work_date)->dayOfWeek] }})
                    </td>                    
                    <td>{{ optional($attendance->clock_in)->format('H:i') ?? '-' }}</td>
                    <td>{{ optional($attendance->clock_out)->format('H:i') ?? '-' }}</td>
                    <td>{{ $attendance->total_break_duration_formatted }}</td>
                    <td>{{ $attendance->work_duration_formatted }}</td>
                    <td><a href="{{ route('attendance.show', $attendance->id) }}" class="attendance-detail">詳細</a></td>
                </tr>
            @endforeach

        </tbody>
    </table>

</div>
@endsection
