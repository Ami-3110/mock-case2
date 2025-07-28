@extends('layouts.admin')

@section('content')
    <h2>{{ $date->format('Y年n月j日') }}の勤怠</h2>

    <div style="margin-bottom: 20px;">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}">前日</a>
        <span>{{ $date->format('Y/m/d') }}</span>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}">翌日</a>
    </div>

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
                    <td><a href="{{ route('admin.attendance.show', $attendance->id) }}" class="attendance-detail">詳細</a></td>
                </tr>

            @empty
                <tr>
                    <td colspan="6">この日の勤怠データはありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
