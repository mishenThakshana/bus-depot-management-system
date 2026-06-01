@extends('layouts.panel')

@section('title', 'Audit Log')
@section('page-label', 'Audit Log')

@section('content')

<div class="page-header">
  <h1 class="page-title">Audit Log</h1>
  <p class="page-subtitle">Login and logout activity for all system users.</p>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('panel.audit-log') }}" class="filter-bar">
  <div class="filter-group">
    <label class="filter-label">User</label>
    <input type="text" name="user_search" class="filter-input" placeholder="Search by name or email…" value="{{ request('user_search') }}">
  </div>

  <div class="filter-group">
    <label class="filter-label">From</label>
    <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}">
  </div>

  <div class="filter-group">
    <label class="filter-label">To</label>
    <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}">
  </div>

  <div class="filter-actions">
    <button type="submit" class="btn-primary">Filter</button>
    @if (request()->hasAny(['user_search', 'date_from', 'date_to']))
      <a href="{{ route('panel.audit-log') }}" class="btn-ghost">Clear</a>
    @endif
  </div>
</form>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Login Activity <span class="table-count">({{ $logs->total() }})</span></span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>User</th>
        <th>Email</th>
        <th>Event</th>
        <th>IP Address</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($logs as $log)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;white-space:nowrap;">
            {{ $log->created_at->format('d M Y, H:i:s') }}
          </td>
          <td>{{ $log->user_name }}</td>
          <td style="color:var(--text-muted);font-size:13px;">{{ $log->user_email }}</td>
          <td>
            @if ($log->event === 'login')
              <span class="badge badge--green">Login</span>
            @elseif ($log->event === 'logout')
              <span class="badge badge--red">Logout</span>
            @else
              <span class="badge badge--amber">Auto Logout</span>
            @endif
          </td>
          <td style="color:var(--text-muted);font-size:13px;">{{ $log->ip_address ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No login activity found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($logs->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border);">
      {{ $logs->links() }}
    </div>
  @endif
</div>

@endsection
