@extends('layouts.admin')

@section('content')
    <h2>勤怠詳細</h2>

    <table class="attendance-application-table">
        <!-- 名前・日付 -->
        <tr class="row-name">
            <th class="cell-label">名前</th>
            <td class="cell-value">{{ $user->name ?? '不明' }}</td>
        </tr>
        <tr class="row-date">
            <th class="cell-label">日付</th>
            <td class="cell-value">{{ $attendanceCorrectRequest->attendance->work_date }}</td>
        </tr>
    
        <!-- 出勤・退勤（修正前 → 修正後） -->
        <tr class="row-clock">
            <th class="cell-label">出勤・退勤</th>
            <td class="cell-value">
                {{ optional($attendanceCorrectRequest->fixed_clock_in)->format('H:i') }} 〜 {{ optional($attendanceCorrectRequest->fixed_clock_out)->format('H:i') }}
            </td>
        </tr>
    
        <!-- 休憩（修正前 → 修正後） -->
        @php
            $originalBreaks = $attendanceCorrectRequest->attendance->breakTimes ?? [];
            $fixedBreaks = json_decode($attendanceCorrectRequest->fixed_breaks ?? '[]');
            $maxBreaks = max(count($originalBreaks), count($fixedBreaks));
        @endphp

        @for ($i = 0; $i < $maxBreaks; $i++)
            <tr class="row-break">
                <th class="cell-label">
                    {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                </th>
                <td class="cell-value">
                    {{ isset($fixedBreaks[$i]->break_start) ? \Carbon\Carbon::parse($fixedBreaks[$i]->break_start)->format('H:i') : '—' }}
                    〜
                    {{ isset($fixedBreaks[$i]->break_end) ? \Carbon\Carbon::parse($fixedBreaks[$i]->break_end)->format('H:i') : '—' }}
                </td>
            </tr>
        @endfor
    
        <!-- 備考 -->
        <tr class="row-reason">
            <th class="cell-label">備考</th>
            <td class="cell-value">{{ $attendanceCorrectRequest->reason ?? '—' }}</td>
        </tr>
    </table>
    @if ($attendanceCorrectRequest->isPending())
        <form method="POST" action="{{ route('admin.correction.approve', $attendanceCorrectRequest->id) }}">
            @csrf
            <button type="submit" class="btn btn-primary">承認する</button>
        </form>
    @else
        <span class="text-success font-bold">承認済み</span>
    @endif


@endsection
