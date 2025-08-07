@extends('layouts.user')
@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance-list.css') }}">
@endsection
@php
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
@endphp
@section('content')
<div class="container">
    <nav class="title">勤怠一覧</nav>

    <div class="month-navigation">
        <a href="{{ route('attendance.list', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}">←前月</a>
      
        <span class="month-center">
            <label for="month-select" class="calendar-trigger">
              <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar">
            </label>
            {{ $year }}/{{ sprintf('%02d', $month) }}
            <input type="month" id="month-select" value="{{ $year }}-{{ sprintf('%02d', $month) }}">
        </span>          
      
        <a href="{{ route('attendance.list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">翌月→</a>
      </div>
      
      <!-- 隠しGETフォーム（routeに丸投げ） -->
      <form id="month-form" action="{{ route('attendance.list') }}" method="GET" style="display:none;">
        <input type="hidden" name="year" id="mf-year" value="{{ $year }}">
        <input type="hidden" name="month" id="mf-month" value="{{ sprintf('%02d',$month) }}">
      </form>
      
    <table class="attendance-table">
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
                    <td class="time">{{ $attendance->clock_in ? $attendance->clock_in->format('H:i') : '未打刻' }}</td>
                    <td class="time">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '未打刻' }}</td></td>
                    <td>{{ $attendance->break_time }}</td>
                    <td>{{ $attendance->work_time }}</td>
                    <td>
                        @if ($attendance->attendanceCorrectRequest && $attendance->attendanceCorrectRequest->status === 'pending')
                        <a href="{{ route('attendance.fixConfirm', $attendance->id) }}" class="attendance-detail">詳細</a>
                        @else
                        <a href="{{ route('attendance.fixForm', $attendance->id) }}" class="attendance-detail">詳細</a>
                        @endif
                      </td>
                </tr>
            @endforeach

        </tbody>
    </table>
</div>

<script>
    (() => {
      const input = document.getElementById('month-select');
      const trigger = document.querySelector('.calendar-trigger');
    
      if (!input || !trigger) return;
    
      // アイコンクリックで月ピッカーを開く
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        // Chrome等: showPicker が使える
        if (input.showPicker) {
          input.showPicker();
        } else {
          // フォールバック: フォーカス → OSに任せる
          input.focus();
          input.click();
        }
      });
    
      // 月が選ばれたら GET で attendance.list に遷移
      input.addEventListener('change', function () {
        const val = this.value; // "YYYY-MM"
        if (!val) return;
        const [y, m] = val.split('-');
    
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = "{{ route('attendance.list') }}";
    
        const fy = document.createElement('input');
        fy.type = 'hidden';
        fy.name = 'year';
        fy.value = y;
    
        const fm = document.createElement('input');
        fm.type = 'hidden';
        fm.name = 'month';
        fm.value = m;
    
        form.appendChild(fy);
        form.appendChild(fm);
        document.body.appendChild(form);
        form.submit();
      });
    })();
    </script>

@endsection