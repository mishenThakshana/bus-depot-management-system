@extends('layouts.panel')

@section('title', 'Dashboard')
@section('page-label', 'Dashboard')

@section('content')

<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <p class="page-subtitle">Overview of depot operations.</p>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Buses in Service</div>
    <div class="stat-value" style="color:var(--accent);">{{ $inServiceBuses }}</div>
    <div class="stat-change">of {{ $totalBuses }} total</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Drivers</div>
    <div class="stat-value" style="color:var(--accent);">{{ $activeDrivers }}</div>
    <div class="stat-change">of {{ $totalDrivers }} total</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Runs Today</div>
    <div class="stat-value" style="color:var(--accent);">{{ $todayScheduled }}</div>
    <div class="stat-change">{{ $todayCancelled }} cancelled</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Off-Service Buses</div>
    <div class="stat-value" style="color:var(--accent);">{{ $totalBuses - $inServiceBuses }}</div>
    <div class="stat-change">of {{ $totalBuses }} total</div>
  </div>
</div>

@if(auth()->user()->isAdmin())
{{-- Two column layout (admin) --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:0;">

  {{-- Today's Runs --}}
  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Today's Runs</span>
      <a href="{{ route('panel.schedules') }}" class="table-action">View all{{ $todayRunsTotal > $todayRuns->count() ? " ($todayRunsTotal)" : '' }}</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Route</th>
          <th>Bus</th>
          <th>Driver</th>
          <th>Departure</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($todayRuns as $run)
        <tr>
          <td>{{ $run->schedule->route->name ?? '—' }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $run->schedule->bus->registration_number ?? '—' }}</td>
          <td>{{ $run->schedule->driver->name ?? '—' }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ \Carbon\Carbon::parse($run->schedule->departure_time)->format('h:i A') }}</td>
          <td>
            @if($run->status === 'scheduled')
              <span class="badge badge--green">Scheduled</span>
            @else
              <span class="badge badge--red">Cancelled</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center; color:var(--text-muted); padding:24px;">No runs scheduled for today.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Recent Activity --}}
  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Recent Activity</span>
      <a href="{{ route('panel.audit-log') }}" class="table-action">View all</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Action</th>
          <th>Subject</th>
          <th>Time</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentActivity as $log)
        <tr>
          <td style="font-size:12px;">{{ $log->user_name }}</td>
          <td style="font-size:12px; color:var(--text-muted);">{{ ucfirst($log->action) }}</td>
          <td style="font-size:12px;">{{ $log->subject_label }}</td>
          <td style="color:var(--text-muted);font-size:11px;">{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align:center; color:var(--text-muted); padding:24px;">No recent activity.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
@else
{{-- Today's Runs full-width (supervisor) --}}
<div style="margin-top:0;">
  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Today's Runs</span>
      <a href="{{ route('panel.schedules') }}" class="table-action">View all{{ $todayRunsTotal > $todayRuns->count() ? " ($todayRunsTotal)" : '' }}</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Route</th>
          <th>Bus</th>
          <th>Driver</th>
          <th>Departure</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($todayRuns as $run)
        <tr>
          <td>{{ $run->schedule->route->name ?? '—' }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $run->schedule->bus->registration_number ?? '—' }}</td>
          <td>{{ $run->schedule->driver->name ?? '—' }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ \Carbon\Carbon::parse($run->schedule->departure_time)->format('h:i A') }}</td>
          <td>
            @if($run->status === 'scheduled')
              <span class="badge badge--green">Scheduled</span>
            @else
              <span class="badge badge--red">Cancelled</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" style="text-align:center; color:var(--text-muted); padding:24px;">No runs scheduled for today.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif

{{-- Alerts row --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px;">

  {{-- Upcoming Maintenance --}}
  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Upcoming Maintenance</span>
      <a href="{{ route('panel.fuel', ['tab' => 'maintenance']) }}" class="table-action">View all{{ $upcomingMaintenanceTotal > $upcomingMaintenance->count() ? " ($upcomingMaintenanceTotal)" : '' }}</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Bus</th>
          <th>Type</th>
          <th>Scheduled</th>
          <th>In</th>
        </tr>
      </thead>
      <tbody>
        @forelse($upcomingMaintenance as $record)
        @php $daysUntil = now()->startOfDay()->diffInDays($record->serviced_date, false); @endphp
        <tr>
          <td style="font-size:12px;">{{ $record->bus->registration_number ?? '—' }}</td>
          <td>{{ $record->maintenance_type }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $record->serviced_date->format('d M Y') }}</td>
          <td>
            @if($daysUntil === 0)
              <span class="badge badge--amber">Today</span>
            @elseif($daysUntil <= 7)
              <span class="badge badge--amber">{{ $daysUntil }}d</span>
            @else
              <span class="badge">{{ $daysUntil }}d</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align:center; color:var(--text-muted); padding:24px;">No upcoming maintenance scheduled.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Licence Renewals --}}
  <div class="table-wrapper">
    <div class="table-header">
      <span class="table-title">Licence Renewals</span>
      <a href="{{ route('panel.drivers', ['licence' => 'soon']) }}" class="table-action">View all{{ $licenceRenewalsTotal > $licenceRenewals->count() ? " ($licenceRenewalsTotal)" : '' }}</a>
    </div>
    <table class="data-table">
      <thead>
        <tr>
          <th>Driver</th>
          <th>Licence No.</th>
          <th>Expires</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($licenceRenewals as $driver)
        @php $daysLeft = now()->startOfDay()->diffInDays($driver->licence_expiry_date, false); @endphp
        <tr>
          <td>{{ $driver->name }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $driver->licence_number }}</td>
          <td style="font-size:12px;">{{ $driver->licence_expiry_date->format('d M Y') }}</td>
          <td>
            @if($daysLeft < 0)
              <span class="badge badge--red">Expired</span>
            @elseif($daysLeft <= 14)
              <span class="badge badge--red">{{ $daysLeft }}d left</span>
            @elseif($daysLeft <= 30)
              <span class="badge badge--amber">{{ $daysLeft }}d left</span>
            @else
              <span class="badge">{{ $daysLeft }}d left</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align:center; color:var(--text-muted); padding:24px;">No licences expiring in the next 60 days.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>

@endsection
