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

        @php
          $req = $submittedRequest ?? null;

          $in  = $req && $req->fixed_clock_in
                ? \Carbon\Carbon::parse($req->fixed_clock_in)->format('H:i')
                : optional($attendance->clock_in)->format('H:i');

          $out = $req && $req->fixed_clock_out
                ? \Carbon\Carbon::parse($req->fixed_clock_out)->format('H:i')
                : optional($attendance->clock_out)->format('H:i');

          $breakSource = ($req && is_array($req->fixed_breaks) && count($req->fixed_breaks))
                ? $req->fixed_breaks
                : $attendance->breakTimes;
        @endphp

        <tr>
          <th>出勤・退勤</th>
          <td>
            <div class="time-group">
              <span class="time">{{ $in }}</span>
              <span class="tilde">〜</span>
              <span class="time">{{ $out }}</span>
            </div>
          </td>
        </tr>

        @foreach ($breakSource as $index => $break)
          @php
            if (is_array($break)) {
                $start = $break['break_start'] ?? null;
                $end   = $break['break_end'] ?? null;
            } else {
                $start = optional($break->break_start)->format('H:i');
                $end   = optional($break->break_end)->format('H:i');
            }
          @endphp
          <tr>
            <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
            <td>
              <div class="time-group">
                <span class="time">{{ $start }}</span>
                <span class="tilde">〜</span>
                <span class="time">{{ $end }}</span>
              </div>
            </td>
          </tr>
        @endforeach

        <tr>
          <th>備考</th>
          <td class="text-left">
            {{ $req->reason ?? '' }}
          </td>
        </tr>
    </table>
    <div class="submitted">
      @if ($submittedRequest)
        @if ($submittedRequest->status === 'pending')
          <p class="submit-message">※承認待ちのため修正はできません</p>
        @elseif ($submittedRequest->status === 'approved')
        <div class="approved">
          <span class="text-success font-bold">承認済み</span>
        </div>
        @endif
      @endif
    </div>    
</div>
@endsection
