@extends('layouts.panel')

@section('title', 'Reports')
@section('page-label', 'Reports')

@section('content')

<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <p class="page-subtitle">Filter and export depot data as PDF or Excel.</p>
</div>

{{-- Tab Bar --}}
<div class="fm-tabs">
  <a href="{{ route('panel.reports', ['tab' => 'fuel']) }}"
     class="fm-tab {{ $tab === 'fuel' ? 'is-active' : '' }}">
    Fuel Consumption
  </a>
  <a href="{{ route('panel.reports', ['tab' => 'maintenance']) }}"
     class="fm-tab {{ $tab === 'maintenance' ? 'is-active' : '' }}">
    Maintenance
  </a>
  <a href="{{ route('panel.reports', ['tab' => 'schedule']) }}"
     class="fm-tab {{ $tab === 'schedule' ? 'is-active' : '' }}">
    Schedule Runs
  </a>
</div>


{{-- ══════════════════════════════════════════════════════════
     FUEL TAB
════════════════════════════════════════════════════════════ --}}
@if ($tab === 'fuel')

<form method="GET" action="{{ route('panel.reports') }}" class="filter-bar">
  <input type="hidden" name="tab" value="fuel" />
  <div class="filter-group">
    <label class="filter-label">From</label>
    <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">To</label>
    <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">Bus</label>
    <select name="bus_id" class="filter-input">
      <option value="">All Buses</option>
      @foreach ($buses as $bus)
        <option value="{{ $bus->id }}" {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
          {{ $bus->registration_number }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="filter-group">
    <label class="filter-label">Driver</label>
    <select name="driver_id" class="filter-input">
      <option value="">All Drivers</option>
      @foreach ($drivers as $driver)
        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
          {{ $driver->name }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn-primary">Filter</button>
    @if (request()->hasAny(['date_from', 'date_to', 'bus_id', 'driver_id']))
      <a href="{{ route('panel.reports', ['tab' => 'fuel']) }}" class="btn-ghost">Clear</a>
    @endif
  </div>
</form>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">
      Fuel Records
      <span class="table-count">({{ $fuelLogs->total() }})</span>
    </span>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('panel.reports.fuel', array_merge(request()->only(['date_from','date_to','bus_id','driver_id']), ['format'=>'pdf'])) }}"
         class="btn-ghost" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
        </svg>
        PDF
      </a>
      <a href="{{ route('panel.reports.fuel', array_merge(request()->only(['date_from','date_to','bus_id','driver_id']), ['format'=>'excel'])) }}"
         class="btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Excel
      </a>
    </div>
  </div>

  @if ($fuelTotals && $fuelLogs->total() > 0)
    <div class="report-summary-bar">
      <div class="report-summary-item">
        <span class="report-summary-label">Total Litres</span>
        <span class="report-summary-value">{{ number_format((float)$fuelTotals->total_litres, 2) }} L</span>
      </div>
      <div class="report-summary-item">
        <span class="report-summary-label">Total Cost</span>
        <span class="report-summary-value">LKR {{ number_format((float)$fuelTotals->sum_cost, 2) }}</span>
      </div>
    </div>
  @endif

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Date</th>
        <th>Litres</th>
        <th>Cost/L (LKR)</th>
        <th>Total Cost (LKR)</th>
        <th>Odometer</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($fuelLogs as $log)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $fuelLogs->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $log->bus->registration_number }}</td>
          <td>{{ $log->driver?->name ?? '—' }}</td>
          <td style="color:var(--text-muted);">{{ $log->fuel_date->format('d M Y') }}</td>
          <td>{{ number_format((float)$log->litres, 1) }} L</td>
          <td style="color:var(--text-muted);">{{ number_format((float)$log->cost_per_litre, 2) }}</td>
          <td style="font-weight:600;">{{ number_format($log->total_cost, 2) }}</td>
          <td style="color:var(--text-muted);">{{ $log->odometer_reading ? number_format($log->odometer_reading) . ' km' : '—' }}</td>
          <td style="color:var(--text-muted);font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->notes ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No fuel records match the selected filters.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($fuelLogs->hasPages())
    <div class="pagination-bar">
      @if ($fuelLogs->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $fuelLogs->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($fuelLogs->getUrlRange(1, $fuelLogs->lastPage()) as $page => $url)
        @if ($page == $fuelLogs->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($fuelLogs->hasMorePages())
        <a href="{{ $fuelLogs->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>

@endif


{{-- ══════════════════════════════════════════════════════════
     MAINTENANCE TAB
════════════════════════════════════════════════════════════ --}}
@if ($tab === 'maintenance')

<form method="GET" action="{{ route('panel.reports') }}" class="filter-bar">
  <input type="hidden" name="tab" value="maintenance" />
  <div class="filter-group">
    <label class="filter-label">From</label>
    <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">To</label>
    <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">Bus</label>
    <select name="bus_id" class="filter-input">
      <option value="">All Buses</option>
      @foreach ($buses as $bus)
        <option value="{{ $bus->id }}" {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
          {{ $bus->registration_number }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="filter-group">
    <label class="filter-label">Type</label>
    <select name="type" class="filter-input">
      <option value="">All Types</option>
      @foreach (\App\Models\MaintenanceRecord::$types as $type)
        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
      @endforeach
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn-primary">Filter</button>
    @if (request()->hasAny(['date_from', 'date_to', 'bus_id', 'type']))
      <a href="{{ route('panel.reports', ['tab' => 'maintenance']) }}" class="btn-ghost">Clear</a>
    @endif
  </div>
</form>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">
      Maintenance Records
      <span class="table-count">({{ $maintenanceRecords->total() }})</span>
    </span>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('panel.reports.maintenance', array_merge(request()->only(['date_from','date_to','bus_id','type']), ['format'=>'pdf'])) }}"
         class="btn-ghost" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
        </svg>
        PDF
      </a>
      <a href="{{ route('panel.reports.maintenance', array_merge(request()->only(['date_from','date_to','bus_id','type']), ['format'=>'excel'])) }}"
         class="btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Excel
      </a>
    </div>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Bus</th>
        <th>Type</th>
        <th>Description</th>
        <th>Serviced Date</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($maintenanceRecords as $record)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $maintenanceRecords->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $record->bus->registration_number }}</td>
          <td>{{ $record->maintenance_type }}</td>
          <td style="color:var(--text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $record->description }}</td>
          <td style="color:var(--text-muted);">{{ $record->serviced_date->format('d M Y') }}</td>
          <td style="color:var(--text-muted);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $record->notes ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No maintenance records match the selected filters.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($maintenanceRecords->hasPages())
    <div class="pagination-bar">
      @if ($maintenanceRecords->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $maintenanceRecords->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($maintenanceRecords->getUrlRange(1, $maintenanceRecords->lastPage()) as $page => $url)
        @if ($page == $maintenanceRecords->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($maintenanceRecords->hasMorePages())
        <a href="{{ $maintenanceRecords->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>

@endif


{{-- ══════════════════════════════════════════════════════════
     SCHEDULE RUNS TAB
════════════════════════════════════════════════════════════ --}}
@if ($tab === 'schedule')

<form method="GET" action="{{ route('panel.reports') }}" class="filter-bar">
  <input type="hidden" name="tab" value="schedule" />
  <div class="filter-group">
    <label class="filter-label">From</label>
    <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">To</label>
    <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}" />
  </div>
  <div class="filter-group">
    <label class="filter-label">Bus</label>
    <select name="bus_id" class="filter-input">
      <option value="">All Buses</option>
      @foreach ($buses as $bus)
        <option value="{{ $bus->id }}" {{ request('bus_id') == $bus->id ? 'selected' : '' }}>
          {{ $bus->registration_number }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="filter-group">
    <label class="filter-label">Status</label>
    <select name="status" class="filter-input">
      <option value="">All Statuses</option>
      <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
      <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn-primary">Filter</button>
    @if (request()->hasAny(['date_from', 'date_to', 'bus_id', 'status']))
      <a href="{{ route('panel.reports', ['tab' => 'schedule']) }}" class="btn-ghost">Clear</a>
    @endif
  </div>
</form>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">
      Schedule Runs
      <span class="table-count">({{ $scheduleRuns->total() }})</span>
    </span>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('panel.reports.schedule', array_merge(request()->only(['date_from','date_to','bus_id','status']), ['format'=>'pdf'])) }}"
         class="btn-ghost" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
        </svg>
        PDF
      </a>
      <a href="{{ route('panel.reports.schedule', array_merge(request()->only(['date_from','date_to','bus_id','status']), ['format'=>'excel'])) }}"
         class="btn-ghost">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Excel
      </a>
    </div>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Run Date</th>
        <th>Route</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Departure</th>
        <th>Arrival</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($scheduleRuns as $run)
        @php $schedule = $run->schedule; @endphp
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $scheduleRuns->firstItem() + $loop->index }}</td>
          <td style="color:var(--text-muted);">{{ $run->run_date->format('d M Y') }}</td>
          <td>{{ $schedule?->route?->name ?? '—' }}</td>
          <td style="font-weight:600;">{{ $schedule?->bus?->registration_number ?? '—' }}</td>
          <td>{{ $schedule?->driver?->name ?? '—' }}</td>
          <td style="color:var(--text-muted);">{{ $schedule ? substr($schedule->departure_time, 0, 5) : '—' }}</td>
          <td style="color:var(--text-muted);">{{ $schedule ? substr($schedule->arrival_time, 0, 5) : '—' }}</td>
          <td>
            @if ($run->status === 'scheduled')
              <span class="status-badge status-badge--active">Scheduled</span>
            @else
              <span class="status-badge" style="color:var(--error);background:#fef2f2;border-color:#fecaca;">Cancelled</span>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No schedule runs match the selected filters.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($scheduleRuns->hasPages())
    <div class="pagination-bar">
      @if ($scheduleRuns->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $scheduleRuns->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($scheduleRuns->getUrlRange(1, $scheduleRuns->lastPage()) as $page => $url)
        @if ($page == $scheduleRuns->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($scheduleRuns->hasMorePages())
        <a href="{{ $scheduleRuns->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>

@endif


<style>
.fm-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--border);
}
.fm-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: color 0.15s, border-color 0.15s;
}
.fm-tab:hover { color: var(--text); }
.fm-tab.is-active { color: var(--accent); border-bottom-color: var(--accent); }
.fm-tab-count {
  font-size: 11px;
  font-weight: 600;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 1px 7px;
  color: var(--text-muted);
}
.fm-tab.is-active .fm-tab-count { background: var(--surface); }

.status-badge {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.03em;
  padding: 2px 8px;
  border-radius: 20px;
  border: 1px solid transparent;
}
.status-badge--active { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }

.report-summary-bar {
  display: flex;
  gap: 24px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--border);
  background: var(--accent-light);
}
.report-summary-item {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.report-summary-label {
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-muted);
}
.report-summary-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--accent);
}
</style>

@endsection
