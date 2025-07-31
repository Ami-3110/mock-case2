@extends('layouts.admin')

@section('content')
    <div class="container">
        <h2 class="title">スタッフ一覧</h2>

        <table class="staff-table">
            <thead>
                <tr class="table-header__row">
                    <th class="header__name">名前</th>
                    <th class="header__email">メールアドレス</th>
                    <th class="header__attencance">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $staff)
                    <tr class="table-date__row">
                        <td class="staff-name">{{ $staff->name }}</td>
                        <td class="staff-email">{{ $staff->email }}</td>
                        <td class="staff-attendance">
                            <a href="{{ route('admin.attendance.staff', $staff->id) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
