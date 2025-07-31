@extends('layouts.admin')

@php
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

@section('content')
    <div class="container">
        <h2 class="title">{{ $user->name }}さんの勤怠</h2>
        <div class="month-navigation">
            <a href="{{ route('admin.attendance.staff', ['id'=> $user->id, 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">
                &lt; {{ $prevMonth->format('Y年n月') }}
            </a>
            <span>{{ $year }}年{{ $month }}月</span>
            <a href="{{ route('admin.attendance.staff', ['id'=> $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">
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
                            {{ $attendance->work_date->format('m/d') }}
                            ({{ $weekdays[$attendance->work_date->dayOfWeek] }})
                        </td>                    
                        <td>{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '未打刻' }}</td>
                        <td>{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '未打刻' }}</td>
                        <td>{{ $attendance->break_time }}</td>
                        <td>{{ $attendance->work_time }}</td>
                        <td><a href="{{ route('attendance.show', $attendance->id) }}" class="attendance-detail">詳細</a></td>
                    </tr>
                @endforeach    
            </tbody>
        </table>
        <div class="btn-wrapper">
            <a href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'year' => $year, 'month' => $month]) }}" class="btn-csv">CSV出力</a>

        </div>
    </div>
    @endsection
    