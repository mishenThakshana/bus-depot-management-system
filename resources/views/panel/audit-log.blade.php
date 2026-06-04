@extends('layouts.panel')

@section('title', 'Audit Log')
@section('page-label', 'Audit Log')

@section('content')

<div class="page-header">
  <h1 class="page-title">Audit Log</h1>
  <p class="page-subtitle">Full activity history across all sections of the system.</p>
</div>

@php
  $tabs = [
    'login'         => 'Login Activity',
    'users'         => 'Users',
    'buses'         => 'Buses',
    'routes'        => 'Routes',
    'drivers'       => 'Drivers',
    'schedules'     => 'Schedules',
    'schedule_runs' => 'Schedule Runs',
    'fuel'          => 'Fuel',
    'maintenance'   => 'Maintenance',
  ];

  $actionColors = [
    'login'       => 'badge--green',
    'logout'      => 'badge--red',
    'auto_logout' => 'badge--amber',
    'created'     => 'badge--green',
    'updated'     => 'badge--blue',
    'deleted'     => 'badge--red',
    'activated'   => 'badge--green',
    'deactivated' => 'badge--red',
    'cancelled'   => 'badge--red',
    'rescheduled' => 'badge--amber',
    'reactivated' => 'badge--green',
  ];

  $actionLabels = [
    'login'       => 'Login',
    'logout'      => 'Logout',
    'auto_logout' => 'Auto Logout',
  ];
@endphp

<div style="display:flex;flex-wrap:wrap;gap:0;margin-bottom:20px;border-bottom:1px solid var(--border);">
  @foreach ($tabs as $key => $label)
    <a href="{{ route('panel.audit-log', array_merge(request()->except('tab', 'page'), ['tab' => $key])) }}"
       class="fm-tab {{ $tab === $key ? 'is-active' : '' }}">
      {{ $label }}
    </a>
  @endforeach
</div>

<form method="GET" action="{{ route('panel.audit-log') }}" class="filter-bar">
  <input type="hidden" name="tab" value="{{ $tab }}">

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
      <a href="{{ route('panel.audit-log', ['tab' => $tab]) }}" class="btn-ghost">Clear</a>
    @endif
  </div>
</form>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">
      {{ $tabs[$tab] ?? 'Activity' }}
      <span class="table-count">({{ $logs->total() }})</span>
    </span>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>User</th>
        <th>Email</th>
        <th>Action</th>
        <th>Details</th>
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
            <span class="badge {{ $actionColors[$log->action] ?? 'badge--amber' }}" style="text-transform:capitalize;">
              {{ $actionLabels[$log->action] ?? $log->action }}
            </span>
          </td>
          <td style="font-size:13px;">{{ $log->subject_label }}</td>
          <td style="color:var(--text-muted);font-size:13px;">{{ $log->ip_address ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">
            No activity found.
          </td>
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
