@extends(Auth::user()->is_admin ? 'layouts.admin' : 'layouts.user')
@section('css')
<link rel="stylesheet" href="{{ asset('css/fix-list.css') }}">
@endsection

@php
    $isAdmin = auth()->check() && auth()->user()->is_admin;
@endphp

@section('content')
<div class="container">
  <nav class="title">申請一覧</nav>

  <!-- タブ -->
  <div class="tabs">
    <div class="tab is-active" data-tab="pending">承認待ち</div>
    <div class="tab" data-tab="approved">承認済み</div>
  </div>

  <!-- 承認待ち -->
  <div class="tab-content active" id="pending">
    <table class="request-table">
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
        @forelse ($pendingApplications as $app)
          <tr>
            <td>{{ $app->status_label }}</td>
            <td>{{ $app->user->name }}</td>
            <td>{{ $app->attendance->work_date->format('Y/m/d') }}</td>
            <td>{{ $app->reason }}</td>
            <td>{{ $app->created_at->format('Y/m/d') }}</td>
            <td>
                @if ($isAdmin)
                  {{-- 管理者：承認画面へ --}}
                  <a href="{{ route('admin.correction.approve', $app->id) }}" class="detail-link">詳細</a>
                @else
                  {{-- 一般ユーザー：確認画面へ --}}
                  <a href="{{ route('attendance.fixConfirm', ['id' => $app->attendance_id, 'request_id' => $app->id]) }}" class="detail-link">詳細</a>
                @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6">データはありません。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- 承認済み -->
  <div class="tab-content" id="approved">
    <table class="request-table">
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
        @forelse ($approvedApplications as $app)
          <tr>
            <td>{{ $app->status_label }}</td>
            <td>{{ $app->user->name }}</td>
            <td>{{ $app->attendance->work_date->format('Y/m/d') }}</td>
            <td>{{ $app->reason }}</td>
            <td>{{ $app->created_at->format('Y/m/d') }}</td>
            <td>
                @if ($isAdmin)
                  {{-- 管理者：承認画面へ --}}
                  <a href="{{ route('admin.correction.approve', $app->id) }}" class="detail-link">詳細</a>
                @else
                  {{-- 一般ユーザー：修正申請フォーム（承認済み）へ --}}
                  <a href="{{ route('attendance.fixConfirm', ['id' => $app->attendance_id, 'request_id' => $app->id]) }}" class="detail-link">詳細</a>
                @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6">データはありません。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- JSでタブ切替（ページ遷移なし） -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');

        const target = tab.getAttribute('data-tab');
        contents.forEach(c => c.classList.toggle('active', c.id === target));
      });
    });
  });
</script>
@endsection