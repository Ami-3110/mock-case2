@extends('layouts.admin')
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/approve.css') }}">
@endsection
@section('content')
<div class="container">
    <nav class="title">勤怠詳細</nav>
    <table class="attendance-application">
        <!-- 名前・日付 -->
        <tr class="row-name">
            <th>名前</th>
            <td>{{ $attendanceCorrectRequest->attendance->user->name ?? '不明' }}</td>
        </tr>
        <tr>
            <th>日付</th>
            <td>
                <div class="date-split">
                    <span class="dste-year">{{ $attendanceCorrectRequest->attendance->work_date->format('Y') }}年</span>
                    <span class="date-md">{{ $attendanceCorrectRequest->attendance->work_date->format('n月j日') }}</span>
                </div>
            </td>
        </tr>    
        <tr>
            <th>出勤・退勤</th>
            <td>
                <div class="time-group">
                    <span class="time">
                    {{ $attendanceCorrectRequest->fixed_clock_in?->format('H:i') ?? '—' }}</span>
                    <span class="tilde">〜</span>
                    <span class="time">{{ $attendanceCorrectRequest->fixed_clock_out?->format('H:i') ?? '—' }}</span>
                </div>
            </td>            
        </tr>    
        <!-- 休憩 -->
        @php
            $originalBreaks = $attendanceCorrectRequest->attendance->breakTimes ?? [];
            $fixedBreaks = is_string($attendanceCorrectRequest->fixed_breaks) ? json_decode($attendanceCorrectRequest->fixed_breaks, true)
        : $attendanceCorrectRequest->fixed_breaks;
            $maxBreaks = max(count($originalBreaks), count($fixedBreaks));
        @endphp
        @for ($i = 0; $i < $maxBreaks; $i++)
        <tr>
            <th>
                {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
            </th>
            <td>
                <div class="time-group">
                    <span class="time">
                    {{ isset($fixedBreaks[$i]['break_start']) ? \Carbon\Carbon::parse($fixedBreaks[$i]['break_start'])->format('H:i') : '—' }}</span>
                    <span class="tilde">〜</span>
                    <span class="time">{{ isset($fixedBreaks[$i]['break_end']) ? \Carbon\Carbon::parse($fixedBreaks[$i]['break_end'])->format('H:i') : '—' }}</span>
                </div>
            </td>
        </tr>
        @endfor
    
        <!-- 備考 -->
        <tr>
            <th>備考</th>
            <td class="cell-value">{{ $attendanceCorrectRequest->reason ?? '—' }}</td>
        </tr>
    </table>
    @if ($attendanceCorrectRequest->isPending())
        <form method="POST" action="{{ route('admin.correction.approve', $attendanceCorrectRequest->id) }}">
            @csrf
            <button type="submit" class="btn btn-primary">承認</button>
        </form>
    @else
        <div class="approved">
            <span class="text-success font-bold">承認済み</span>
        </div>
    @endif
</div>
@endsection
