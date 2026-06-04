@extends('layouts.panel')

@section('title', 'Schedules')
@section('page-label', 'Schedules')

@section('content')

@php
  $canAddSchedule = $routes->isNotEmpty() && $buses->isNotEmpty() && $drivers->isNotEmpty();
@endphp

<div class="page-header">
  <h1 class="page-title">Schedules</h1>
  <p class="page-subtitle">Assign a bus and driver to a route, and the dates it runs.</p>
</div>

@if (session('success'))
  <div class="alert alert--success">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    {{ session('success') }}
  </div>
@endif

@if (session('error'))
  <div class="alert alert--error">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    {{ session('error') }}
  </div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Schedules <span class="table-count">({{ $schedules->total() }})</span></span>
    @if ($canAddSchedule)
      <button class="btn-primary" onclick="openAddModal()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Schedule
      </button>
    @endif
  </div>

  @unless ($canAddSchedule)
    <div class="alert alert--error" style="margin:16px;">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      You need at least one active route, one in-service bus, and one active driver before creating a schedule.
    </div>
  @endunless

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Route</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Departure</th>
        <th>Arrival</th>
        <th>Frequency</th>
        <th>Coverage</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($schedules as $schedule)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $schedules->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $schedule->route?->name ?? '—' }}</td>
          <td>{{ $schedule->bus?->registration_number ?? '—' }}</td>
          <td>{{ $schedule->driver?->name ?? '—' }}</td>
          <td style="letter-spacing:0.03em;">{{ \Illuminate\Support\Str::substr($schedule->departure_time, 0, 5) }}</td>
          <td style="letter-spacing:0.03em;">{{ \Illuminate\Support\Str::substr($schedule->arrival_time, 0, 5) }}</td>
          <td style="text-transform:capitalize;">
            {{ $schedule->frequency }}
            @if ($schedule->frequency === 'weekly' && $schedule->days_of_week)
              <div style="margin-top:2px;color:var(--text-muted);font-size:11px;text-transform:none;">
                {{ collect(\App\Models\Schedule::$weekdays)->only($schedule->days_of_week)->map(fn ($d) => \Illuminate\Support\Str::substr($d, 0, 3))->implode(', ') }}
              </div>
            @endif
          </td>
          <td style="color:var(--text-muted);font-size:12px;">
            {{ $schedule->start_date->format('d M Y') }} &ndash; {{ $schedule->end_date->format('d M Y') }}
            <div style="margin-top:2px;">{{ $schedule->runs_count }} {{ \Illuminate\Support\Str::plural('run', $schedule->runs_count) }}</div>
          </td>
          <td>
            @if ($schedule->is_active)
              <span class="status-badge status-badge--active">Active</span>
            @else
              <span class="status-badge status-badge--inactive">Inactive</span>
            @endif
          </td>
          <td style="white-space:nowrap;">
            <button
              class="btn-ghost btn-ghost--sm"
              onclick="window.location='{{ route('panel.schedules.runs', $schedule) }}'"
            >Schedule</button>
            <button
              class="btn-ghost btn-ghost--sm"
              onclick="openEditModal({{ json_encode([
                'id'             => $schedule->id,
                'bus_route_id'   => $schedule->bus_route_id,
                'bus_id'         => $schedule->bus_id,
                'driver_id'      => $schedule->driver_id,
                'departure_time' => \Illuminate\Support\Str::substr($schedule->departure_time, 0, 5),
                'arrival_time'   => \Illuminate\Support\Str::substr($schedule->arrival_time, 0, 5),
                'frequency'      => $schedule->frequency,
                'days_of_week'   => $schedule->days_of_week ?? [],
                'start_date'     => $schedule->start_date->format('Y-m-d'),
                'end_date'       => $schedule->end_date->format('Y-m-d'),
                'is_active'      => $schedule->is_active,
              ]) }})"
            >Edit</button>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="10" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No schedules found. Add one using the button above.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>


  @if ($schedules->hasPages())
    <div class="pagination-bar">
      @if ($schedules->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $schedules->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($schedules->getUrlRange(1, $schedules->lastPage()) as $page => $url)
        @if ($page == $schedules->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($schedules->hasMorePages())
        <a href="{{ $schedules->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>


{{-- ── Add Schedule Modal ── --}}
<div class="modal-overlay" id="addScheduleModal" onclick="closeAddModalOnOverlay(event)">
  <div class="modal" style="max-width:520px;">

    <div class="modal-header">
      <h2 class="modal-title">Add Schedule</h2>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" action="{{ route('panel.schedules.store') }}" novalidate id="addScheduleForm">
      @csrf

      <div class="modal-body">

        @if ($errors->any() && ! old('_edit_id'))
          <div class="alert alert--error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
              @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
              @endforeach
            </div>
          </div>
        @endif

        <div class="field">
          <label for="bus_route_id">Route</label>
          <select id="bus_route_id" name="bus_route_id" required>
            <option value="" disabled {{ old('bus_route_id') ? '' : 'selected' }}>Select route…</option>
            @foreach ($routes as $route)
              <option value="{{ $route->id }}" {{ (string) old('bus_route_id') === (string) $route->id ? 'selected' : '' }}>{{ $route->name }}</option>
            @endforeach
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="bus_id">Bus</label>
            <select id="bus_id" name="bus_id" required>
              <option value="" disabled {{ old('bus_id') ? '' : 'selected' }}>Select bus…</option>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}" {{ (string) old('bus_id') === (string) $bus->id ? 'selected' : '' }}>{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="driver_id">Driver</label>
            <select id="driver_id" name="driver_id" required>
              <option value="" disabled {{ old('driver_id') ? '' : 'selected' }}>Select driver…</option>
              @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" {{ (string) old('driver_id') === (string) $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="departure_time">Departure Time</label>
            <input type="time" id="departure_time" name="departure_time" value="{{ old('departure_time') }}" required />
          </div>
          <div class="field">
            <label for="arrival_time">Arrival Time</label>
            <input type="time" id="arrival_time" name="arrival_time" value="{{ old('arrival_time') }}" required />
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="frequency">Frequency</label>
          <select id="frequency" name="frequency" required onchange="toggleDays('add')">
            <option value="" disabled {{ old('frequency') ? '' : 'selected' }}>How often does it run?</option>
            @foreach (\App\Models\Schedule::$frequencies as $frequency)
              <option value="{{ $frequency }}" {{ old('frequency') === $frequency ? 'selected' : '' }} style="text-transform:capitalize;">{{ ucfirst($frequency) }}</option>
            @endforeach
          </select>
        </div>

        @php $oldAddDays = array_map('intval', (array) old('days_of_week', [])); @endphp
        <div class="field days-field" id="add_days_field" style="margin-top:14px;{{ old('frequency') === 'weekly' ? '' : 'display:none;' }}">
          <label>Runs on</label>
          <div class="weekday-grid">
            @foreach (\App\Models\Schedule::$weekdays as $num => $name)
              <label class="weekday-chip">
                <input type="checkbox" name="days_of_week[]" value="{{ $num }}" {{ in_array($num, $oldAddDays, true) ? 'checked' : '' }} />
                <span>{{ \Illuminate\Support\Str::substr($name, 0, 3) }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required />
          </div>
          <div class="field">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}" required />
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn-primary">Add Schedule</button>
      </div>
    </form>
  </div>
</div>


{{-- ── Edit Schedule Modal ── --}}
<div class="modal-overlay" id="editScheduleModal" onclick="closeEditModalOnOverlay(event)">
  <div class="modal" style="max-width:520px;">

    <div class="modal-header">
      <h2 class="modal-title">Edit Schedule</h2>
      <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" id="editScheduleForm" novalidate>
      @csrf
      @method('PATCH')
      <input type="hidden" name="_edit_id" id="edit_id" value="{{ old('_edit_id') }}" />

      <div class="modal-body">

        @if ($errors->any() && old('_edit_id'))
          <div class="alert alert--error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
              @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
              @endforeach
            </div>
          </div>
        @endif

        <div class="field">
          <label for="edit_bus_route_id">Route</label>
          <select id="edit_bus_route_id" name="bus_route_id" required>
            @foreach ($routes as $route)
              <option value="{{ $route->id }}">{{ $route->name }}</option>
            @endforeach
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_bus_id">Bus</label>
            <select id="edit_bus_id" name="bus_id" required>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}">{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="edit_driver_id">Driver</label>
            <select id="edit_driver_id" name="driver_id" required>
              @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_departure_time">Departure Time</label>
            <input type="time" id="edit_departure_time" name="departure_time" required />
          </div>
          <div class="field">
            <label for="edit_arrival_time">Arrival Time</label>
            <input type="time" id="edit_arrival_time" name="arrival_time" required />
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="edit_frequency">Frequency</label>
          <select id="edit_frequency" name="frequency" required onchange="toggleDays('edit')">
            @foreach (\App\Models\Schedule::$frequencies as $frequency)
              <option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field days-field" id="edit_days_field" style="margin-top:14px;display:none;">
          <label>Runs on</label>
          <div class="weekday-grid">
            @foreach (\App\Models\Schedule::$weekdays as $num => $name)
              <label class="weekday-chip">
                <input type="checkbox" name="days_of_week[]" value="{{ $num }}" data-day="{{ $num }}" />
                <span>{{ \Illuminate\Support\Str::substr($name, 0, 3) }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_start_date">Start Date</label>
            <input type="date" id="edit_start_date" name="start_date" required />
          </div>
          <div class="field">
            <label for="edit_end_date">End Date</label>
            <input type="date" id="edit_end_date" name="end_date" required />
          </div>
        </div>

        <div style="margin-top:16px;">
          <label style="font-size:13px;font-weight:500;color:var(--text);display:block;margin-bottom:8px;">Status</label>
          <label class="toggle-wrap">
            <span class="toggle-track">
              <input type="checkbox" id="edit_is_active" name="is_active" value="1" onchange="document.getElementById('edit_status_label').textContent = this.checked ? 'Active' : 'Inactive'" />
              <span class="toggle-knob"></span>
            </span>
            <span id="edit_status_label" style="font-size:13px;color:var(--text);">Active</span>
          </label>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>


<style>
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
.status-badge--active    { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.status-badge--inactive  { color: var(--text-muted); background: var(--bg); border-color: var(--border); }

.toggle-wrap  { display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; }
.toggle-track { position:relative; width:36px; height:20px; flex-shrink:0; }
.toggle-track input { opacity:0; width:0; height:0; position:absolute; }
.toggle-knob  { position:absolute; inset:0; background:var(--border); border-radius:20px; transition:background 0.18s; cursor:pointer; }
.toggle-knob::before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform 0.18s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.toggle-track input:checked + .toggle-knob { background:var(--success); }
.toggle-track input:checked + .toggle-knob::before { transform:translateX(16px); }

.weekday-grid { display:flex; flex-wrap:wrap; gap:8px; }
.weekday-chip { position:relative; cursor:pointer; user-select:none; }
.weekday-chip input { position:absolute; opacity:0; width:0; height:0; }
.weekday-chip span {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:46px; padding:7px 10px; font-size:12px; font-weight:600;
  color:var(--text-muted); background:var(--bg);
  border:1px solid var(--border); border-radius:8px; transition:all 0.15s;
}
.weekday-chip input:checked + span { color:#fff; background:var(--primary, #2563eb); border-color:var(--primary, #2563eb); }
.weekday-chip input:focus-visible + span { box-shadow:0 0 0 2px rgba(37,99,235,.3); }
</style>


<script>
  // ─── Frequency → weekday picker ───────────────────────────
  // Show the weekday chips only for weekly schedules; clear them otherwise so
  // a daily schedule never submits leftover days.
  function toggleDays(which) {
    const isWeekly = document.getElementById(which === 'edit' ? 'edit_frequency' : 'frequency').value === 'weekly';
    const field = document.getElementById(which === 'edit' ? 'edit_days_field' : 'add_days_field');
    field.style.display = isWeekly ? '' : 'none';
    if (!isWeekly) {
      field.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
    }
  }


  // ─── Add Modal ────────────────────────────────────────────
  const addModal = document.getElementById('addScheduleModal');

  function openAddModal() {
    addModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('bus_route_id')?.focus());
  }

  function closeAddModal() {
    addModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeAddModalOnOverlay(e) {
    if (e.target === addModal) closeAddModal();
  }

  @if ($errors->any() && ! old('_edit_id'))
    document.addEventListener('DOMContentLoaded', openAddModal);
  @endif


  // ─── Edit Modal ───────────────────────────────────────────
  const editModal = document.getElementById('editScheduleModal');
  const editForm  = document.getElementById('editScheduleForm');

  function openEditModal(schedule) {
    editForm.action = '/panel/schedules/' + schedule.id;

    document.getElementById('edit_id').value             = schedule.id;
    document.getElementById('edit_bus_route_id').value   = schedule.bus_route_id;
    document.getElementById('edit_bus_id').value         = schedule.bus_id;
    document.getElementById('edit_driver_id').value      = schedule.driver_id;
    document.getElementById('edit_departure_time').value = schedule.departure_time;
    document.getElementById('edit_arrival_time').value   = schedule.arrival_time;
    document.getElementById('edit_frequency').value      = schedule.frequency;
    document.getElementById('edit_start_date').value     = schedule.start_date;
    document.getElementById('edit_end_date').value       = schedule.end_date;

    const days = (schedule.days_of_week || []).map(Number);
    document.querySelectorAll('#edit_days_field input[type=checkbox]').forEach(cb => {
      cb.checked = days.includes(Number(cb.dataset.day));
    });
    toggleDays('edit');

    const activeToggle = document.getElementById('edit_is_active');
    activeToggle.checked = !!schedule.is_active;
    document.getElementById('edit_status_label').textContent = schedule.is_active ? 'Active' : 'Inactive';

    editModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('edit_bus_route_id')?.focus());
  }

  function closeEditModal() {
    editModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeEditModalOnOverlay(e) {
    if (e.target === editModal) closeEditModal();
  }

  @if ($errors->any() && old('_edit_id'))
    document.addEventListener('DOMContentLoaded', () => openEditModal({
      id:             {{ (int) old('_edit_id') }},
      bus_route_id:   {{ json_encode(old('bus_route_id')) }},
      bus_id:         {{ json_encode(old('bus_id')) }},
      driver_id:      {{ json_encode(old('driver_id')) }},
      departure_time: {{ json_encode(old('departure_time')) }},
      arrival_time:   {{ json_encode(old('arrival_time')) }},
      frequency:      {{ json_encode(old('frequency')) }},
      days_of_week:   {{ json_encode(array_map('intval', (array) old('days_of_week', []))) }},
      start_date:     {{ json_encode(old('start_date')) }},
      end_date:       {{ json_encode(old('end_date')) }},
      is_active:      {{ old('is_active') ? 'true' : 'false' }},
    }));
  @endif
</script>

@endsection
