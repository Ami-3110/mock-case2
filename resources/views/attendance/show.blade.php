@extends($layout)

@section('content')
<div class="container">
    <h2>勤怠詳細</h2>
    <form action="{{ route('attendance.fix', ['id' => $attendance->id]) }}" method="POST">
        @csrf
        <table class="attendance-edit-table">
            <!-- 名前・日付 -->
            <tr class="row-name">
                <th class="cell-label">名前</th>
                <td class="cell-value">{{ $attendance->user->name ?? '不明' }}</td>
            </tr>
            <tr class="row-date">
                <th class="cell-label">日付</th>
                <td class="cell-value">{{ $attendance->work_date }}</td>
            </tr>
    
            <!-- 出勤・退勤 -->
            <tr class="row-clock">
                <th class="cell-label">出勤・退勤</th>
                <td class="cell-input">
                    @if ($isEditable)
                        <input type="time" name="clock_in" value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}" class="input-clock-in">
                        〜
                        <input type="time" name="clock_out" value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}" class="input-clock-out">
                    @else
                        {{ optional($attendance->clock_in)->format('H:i') }} 〜 {{ optional($attendance->clock_out)->format('H:i') }}
                    @endif
                </td>
            </tr>
    
            <!-- 休憩 -->
            @php $breakCount = count($breaks); @endphp
            @for ($i = 0; $i < $breakCount + 1; $i++)
                <tr class="row-break">
                    <th class="cell-label">
                        {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                    </th>
                    <td class="cell-input">
                        @if ($isEditable)
                            <input type="time" name="breaks[{{ $i }}][break_start]"
                                value="{{ old("breaks.$i.break_start", optional($breaks[$i]->break_start ?? null)->format('H:i')) }}"
                                class="input-break-start">
                            〜
                            <input type="time" name="breaks[{{ $i }}][break_end]"
                                value="{{ old("breaks.$i.break_end", optional($breaks[$i]->break_end ?? null)->format('H:i')) }}"
                                class="input-break-end">
                        @else
                            {{ optional($breaks[$i]->break_start ?? null)->format('H:i') ?? '—' }}
                            〜
                            {{ optional($breaks[$i]->break_end ?? null)->format('H:i') ?? '—' }}
                        @endif
                    </td>
                </tr>
            @endfor
    
            <!-- 備考 -->
            <tr class="row-reason">
                <th class="cell-label">備考</th>
                <td class="cell-input">
                    @if ($isEditable)
                    <input type="text" name="reason" value="{{ old('reason', optional($attendance->attendanceCorrectRequest)->reason) }}">
                    @else
                        {{ $attendance->attendanceCorrectRequest->reason ?? '—' }}
                    @endif
                </td>
            </tr>
        </table>
    
        @if ($isEditable)
            <div class="button-rapper">
                <button type="submit">修正</button>
            </div>
        @else
            <div class="attention">
                ※承認待ちのため修正はできません。
            </div>
        @endif
    </form>
    
</div>
@endsection

