@extends('layouts.admin')
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-fix.css') }}">
@endsection
@section('content')
@if(session('success'))
  <div class="alert alert-success">
    <span class="success-message">{{ session('success') }}</span>
  </div>
@endif
<div class="container">
    <nav class="title">勤怠詳細</nav>
    <form action="{{ route('admin.attendances.fix', ['id' => $attendance->id]) }}" method="POST">
        @csrf
        <table class="fix-form">
            <tr>
                <th>名前</th>
                <td>{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td><div class="date-split">
                    <span class="date-year">{{ $attendance->work_date->format('Y') }}年</span>
                    <span class="date-md">{{ $attendance->work_date->format('n月j日') }}</span>
                  </div>
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td>
                    <div class="time-group">
                        <input class="form time" type="time" name="fixed_clock_in" value="{{ old('fixed_clock_in', optional($attendance->clock_in)->format('H:i')) }}">
                        〜
                        <input class="form time" type="time" name="fixed_clock_out" value="{{ old('fixed_clock_out', optional($attendance->clock_out)->format('H:i')) }}">
                    </div>
                    @error('clock_in')
                    <div class="error">{{ $message }}</div>
                    @enderror
                    @error('clock_out')
                    <div class="error">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            @foreach ($breaks as $index => $break)
            <tr>
                <th>{{ $break->label }}</th>
                <td>
                    <div class="time-group">
                        <input class="form time" type="time" name="breaks[{{ $index }}][break_start]" value="{{ optional($break->start)->format('H:i') }}">                        
                        〜
                        <input class="form time" type="time" name="breaks[{{ $index }}][break_end]" value="{{ optional($break->end)->format('H:i') }}">                        
                    </div>
                    @error("fixed_breaks.$index.break_start")
                    <div class="error">{{ $message }}</div>
                    @enderror
                    @error("fixed_breaks.$index.break_end")
                    <div class="error">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            @endforeach
            <tr>
                <th>備考</th>
                <td>
                    <textarea class="reason" name="reason">{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
        </table>
        <div class="btn-wrapper">
            <button class="submit-btn" type="submit">修正</button>
        </div>
    </form>
@endsection
