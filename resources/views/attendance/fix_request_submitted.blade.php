@extends('layouts.user')
@section('css')
<link rel="stylesheet" href="{{ asset('css/user/fix-confirm.css') }}">
@endsection
@section('content')
<div class="container">
    <nav class="title">勤怠詳細</nav>
    <table class="confirm-table">
        <tr>
            <th>名前</th>
            <td>{{ $attendance->user->name }}</td>
        </tr>    
        <tr>
          <th>日付</th>
          <td>
            <div class="date-split">
              <span class="date-year">{{ $attendance->work_date->format('Y') }}年</span>
              <span class="date-md">{{ $attendance->work_date->format('n月j日') }}</span>
            </div>
          </td>
        </tr>    
        <tr>
          <th>出勤・退勤</th>
          <td>
            <div class="time-group">
              <span class="time">
                {{ optional($attendance->fixed_clock_in ?? $attendance->clock_in)->format('H:i') }}
              </span>
              <span class="tilde">〜</span>
              <span class="time">
                {{ optional($attendance->fixed_clock_out ?? $attendance->clock_out)->format('H:i') }}
              </span>
            </div>
          </td>
        </tr>    
        @foreach (($attendance->fixed_breaks ?? $attendance->breakTimes) as $index => $break)
          <tr>
            <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
            <td>
              <div class="time-group">
                <span class="time">
                  {{ optional($break['break_start'] ?? $break->break_start)->format('H:i') }}
                </span>
                <span class="tilde">〜</span>
                <span class="time">
                  {{ optional($break['break_end'] ?? $break->break_end)->format('H:i') }}
                </span>
              </div>
            </td>
          </tr>
        @endforeach    
        <tr>
          <th>備考</th>
          <td class="text-left">{{ $attendance->reason ?? ($attendance->attendanceCorrectRequest->reason ?? '') }}</td>
        </tr>
    </table>
    <div class="submitted">
        <p class="submit-message">※承認待ちのため修正はできません</p>
    </div>
</div>
@endsection
