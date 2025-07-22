@extends('layouts.user')

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
            @php
                function secondsToTime($seconds) {
                    $h = floor($seconds / 3600);
                    $m = floor(($seconds % 3600) / 60);
                    return sprintf('%02d:%02d', $h, $m);
                }
            @endphp
            @foreach ($attendances as $attendance)
            @php
                $totalBreakSeconds = $attendance->breakTimes->sum(function($break) {
                    if ($break->break_start && $break->break_end) {
                        return $break->break_end->diffInSeconds($break->break_start, true);
                    }
                    return 0;
                });
            
                $workSeconds = 0;
                if ($attendance->clock_in && $attendance->clock_out) {
                    $workSeconds = $attendance->clock_out
                        ->diffInSeconds($attendance->clock_in, true)
                        - $totalBreakSeconds;
                }
            @endphp
        
                    
                <tr>
                    @php
                        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
                        $date = \Carbon\Carbon::parse($attendance->work_date);
                    @endphp
                    <td>{{ $date->format('m/d') }}({{ $weekdays[$date->dayOfWeek] }})</td>
                    <td>{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '-' }}</td>
                    <td>{{ secondsToTime($totalBreakSeconds) }}</td>
                    <td>{{ $workSeconds > 0 ? secondsToTime($workSeconds) : '-' }}</td>
                    <td><a href="{{-- route('/attendance.show', $attendance->id) --}}" class="attendance-detail">詳細</a></td>
                </tr>
            @endforeach

        </tbody>
    </table>

</div>
@endsection
