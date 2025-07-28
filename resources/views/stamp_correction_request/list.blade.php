@extends(Auth::user()->is_admin ? 'layouts.admin' : 'layouts.user')

@section('content')

<div class="tabs">
    <div class="tab active" data-tab="pending">承認待ち</div>
    <div class="tab" data-tab="approved">承認済み</div>
</div>

<div id="pending" class="tab-content active">
    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pendingApplications as $application)
                <tr>
                    <td>{{ $application->status }}</td>
                    <td>{{ $application->user->name }}</td>
                    <td>{{ $application->attendance->work_date ? $application->attendance->work_date->format('Y/m/d') : '不明' }}
                    </td>
                    <td>{{ $application->reason }}</td>
                    <td>{{ $application->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance.show', $application->attendance_id) }}" class="attendance-detail">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div id="approved" class="tab-content">
    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($approvedApplications as $application)
                <tr>
                    <td>{{ $application->status }}</td>
                    <td>{{ $application->user->name }}</td>
                    <td>{{ $application->attendance->work_date ? \Carbon\Carbon::parse($application->attendance->work_date)->format('Y/m/d') : '不明' }}
                    </td>
                    <td>{{ $application->reason }}</td>
                    <td>{{ $application->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance.show', $application->attendance_id) }}" class="attendance-detail">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });
</script>
@endsection
