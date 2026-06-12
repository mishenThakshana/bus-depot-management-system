@extends('layouts.panel')

@section('title', 'Schedules')
@section('page-label', 'Schedules')

@section('content')

@php
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Str;

  // Query-string helpers — keep active filters when switching view / paging months.
  $baseParams = request()->except(['view', 'page', 'month']);

  // Calendar grid geometry.
  $start         = $month->copy()->startOfMonth();
  $daysInMonth   = $month->daysInMonth;
  $leadingBlanks = ($start->dayOfWeek + 6) % 7; // Monday-first grid
  $todayIso      = $today->toDateString();

  $totalRuns  = $byDate->flatten(1)->reject(fn ($r) => $r['cancelled'])->count();
  $activeDays = $byDate->filter(fn ($day) => $day->contains(fn ($r) => ! $r['cancelled']))->count();

  // List view: group the current page of runs by day, then by timeslot.
  $listGroups = $runs->getCollection()
    ->groupBy(fn ($r) => $r->run_date->toDateString())
    ->map(fn ($day) => $day->groupBy(fn ($r) =>
      Str::substr($r->schedule->departure_time, 0, 5) . '–' . Str::substr($r->schedule->arrival_time, 0, 5)));
@endphp

<div class="page-header">
  <h1 class="page-title">Schedules</h1>
  <p class="page-subtitle">Every scheduled run across the depot. Switch between calendar and list, and filter by driver, bus, date or timeslot.</p>
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

  <div class="sched-header">
    <div class="view-toggle">
      <a href="{{ route('panel.schedules', array_merge($baseParams, ['view' => 'calendar'])) }}"
         class="view-toggle__btn {{ $view === 'calendar' ? 'is-active' : '' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Calendar
      </a>
      <a href="{{ route('panel.schedules', array_merge($baseParams, ['view' => 'list'])) }}"
         class="view-toggle__btn {{ $view === 'list' ? 'is-active' : '' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        List
      </a>
    </div>

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

  {{-- ── Filter bar ── --}}
  <form method="GET" action="{{ route('panel.schedules') }}" class="filter-bar">
    <input type="hidden" name="view" value="{{ $view }}" />

    <div class="filter-field">
      <label>Driver</label>
      <select name="driver_id">
        <option value="">All drivers</option>
        @foreach ($filterDrivers as $d)
          <option value="{{ $d->id }}" {{ (string) $filters['driver_id'] === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="filter-field">
      <label>Bus</label>
      <select name="bus_id">
        <option value="">All buses</option>
        @foreach ($filterBuses as $b)
          <option value="{{ $b->id }}" {{ (string) $filters['bus_id'] === (string) $b->id ? 'selected' : '' }}>{{ $b->registration_number }}</option>
        @endforeach
      </select>
    </div>

    <div class="filter-field">
      <label>Date from</label>
      <input type="date" name="date_from" value="{{ $filters['date_from'] }}" />
    </div>
    <div class="filter-field">
      <label>Date to</label>
      <input type="date" name="date_to" value="{{ $filters['date_to'] }}" />
    </div>

    <div class="filter-field">
      <label>Timeslot from</label>
      <input type="time" name="time_from" value="{{ $filters['time_from'] }}" />
    </div>
    <div class="filter-field">
      <label>Timeslot to</label>
      <input type="time" name="time_to" value="{{ $filters['time_to'] }}" />
    </div>

    <div class="filter-actions">
      <button type="submit" class="btn-primary btn-primary--sm">Apply</button>
      @if ($hasFilters)
        <a href="{{ route('panel.schedules', ['view' => $view]) }}" class="btn-ghost btn-ghost--sm">Clear</a>
      @endif
    </div>
  </form>


  {{-- ════════════════ CALENDAR VIEW ════════════════ --}}
  @if ($view === 'calendar')
    <div class="cal-toolbar">
      <a href="{{ route('panel.schedules', array_merge($baseParams, ['view' => 'calendar', 'month' => $prevMonth])) }}" class="cal-nav" aria-label="Previous month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <span class="cal-title">{{ $month->format('F Y') }}</span>
      <a href="{{ route('panel.schedules', array_merge($baseParams, ['view' => 'calendar', 'month' => $nextMonth])) }}" class="cal-nav" aria-label="Next month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>

    <div class="cal-summary">
      <span><strong>{{ $totalRuns }}</strong> runs scheduled</span>
      <span class="cal-summary__dot">•</span>
      <span><strong>{{ $activeDays }}</strong> operating {{ Str::plural('day', $activeDays) }}</span>
      @unless ($month->isSameMonth($today))
        <a href="{{ route('panel.schedules', array_merge($baseParams, ['view' => 'calendar'])) }}" class="cal-today-link">Jump to today</a>
      @endunless
    </div>

    <div class="cal-weekdays">
      <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
    </div>

    <div class="cal-grid">
      @for ($i = 0; $i < $leadingBlanks; $i++)
        <div class="cal-cell cal-cell--blank"></div>
      @endfor

      @for ($day = 1; $day <= $daysInMonth; $day++)
        @php
          $iso         = $start->copy()->day($day)->toDateString();
          $dayRuns     = $byDate->get($iso, collect());
          $activeCount = $dayRuns->reject(fn ($r) => $r['cancelled'])->count();
          $slotCount   = $dayRuns->reject(fn ($r) => $r['cancelled'])
                                 ->map(fn ($r) => $r['departure'].'-'.$r['arrival'])->unique()->count();
          $isToday     = $iso === $todayIso;
          $isPast      = $iso < $todayIso;
          $hasRuns     = $dayRuns->isNotEmpty();
        @endphp
        <div
          class="cal-cell @if ($isToday) cal-cell--today @elseif ($isPast && ! $hasRuns) cal-cell--muted @endif @if ($hasRuns) cal-cell--has @endif"
          @if ($hasRuns) onclick="openDay('{{ $iso }}')" role="button" tabindex="0" onkeydown="if(event.key==='Enter')openDay('{{ $iso }}')" @endif
        >
          <span class="cal-cell__num">{{ $day }}</span>
          @if ($activeCount > 0)
            <span class="cal-count">{{ $activeCount }}</span>
            <span class="cal-cell__meta">{{ $slotCount }} {{ Str::plural('slot', $slotCount) }}</span>
          @elseif ($hasRuns)
            <span class="cal-cell__meta cal-cell__meta--cancelled">all cancelled</span>
          @endif
        </div>
      @endfor
    </div>
  @endif


  {{-- ════════════════ LIST VIEW ════════════════ --}}
  @if ($view === 'list')
    <div class="run-list">
      @forelse ($listGroups as $date => $slots)
        @php
          $dayCarbon  = Carbon::parse($date);
          $dayActive  = $slots->flatten(1)->reject(fn ($r) => $r->isCancelled())->count();
        @endphp
        <div class="run-day">
          <div class="run-day__head">
            <span class="run-day__date">{{ $dayCarbon->format('D, d M Y') }}</span>
            <span class="run-day__count">{{ $dayActive }} {{ Str::plural('run', $dayActive) }}</span>
          </div>

          @foreach ($slots as $slot => $slotRuns)
            <div class="run-slot">
              <div class="run-slot__head">
                <span class="run-slot__time">{{ $slot }}</span>
                <span class="run-slot__count">{{ $slotRuns->count() }} {{ Str::plural('bus', $slotRuns->count()) }}</span>
              </div>

              @foreach ($slotRuns as $run)
                @php
                  $past   = $run->isPast();
                  $sid    = $run->schedule_id;
                  $rid    = $run->id;
                  $iso    = $run->run_date->toDateString();
                  $pretty = $run->run_date->format('d M Y');
                  $route  = $run->schedule?->route?->name ?? '—';
                @endphp
                <div class="day-run {{ $run->isCancelled() ? 'day-run--cancelled' : '' }} {{ $past ? 'day-run--past' : '' }}">
                  <div class="day-run__main">
                    <div class="day-run__route">{{ $route }}</div>
                    <div class="day-run__sub">
                      <span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        {{ $run->schedule?->bus?->registration_number ?? '—' }}
                      </span>
                      <span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/></svg>
                        {{ $run->schedule?->driver?->name ?? '—' }}
                      </span>
                    </div>
                  </div>
                  <div class="day-run__actions">
                    @if ($past)
                      <span class="day-tag day-tag--past">Past</span>
                    @else
                      @if ($run->isCancelled())
                        <span class="day-tag day-tag--cancelled">Cancelled</span>
                        <button class="day-btn" onclick="reactivateRun({{ $sid }}, {{ $rid }}, '{{ $pretty }}')">Reactivate</button>
                      @else
                        <button class="day-btn" onclick="openReschedule({{ $sid }}, {{ $rid }}, '{{ $iso }}', @js($route), '{{ $pretty }}')">Reschedule</button>
                        <button class="day-btn day-btn--danger" onclick="cancelRun({{ $sid }}, {{ $rid }}, '{{ $pretty }}')">Cancel</button>
                      @endif
                      <button class="day-btn" onclick="openEditModal(SCHEDULES[{{ $sid }}])" title="Edit recurring schedule">Edit</button>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      @empty
        <div class="run-empty">
          No runs {{ $hasFilters ? 'match these filters' : 'scheduled yet' }}.
        </div>
      @endforelse
    </div>

    @if ($runs->total() > 0)
      @php
        $last = $runs->lastPage();
        $cur  = $runs->currentPage();
        // First, last, and a small window around the current page — keeps the
        // bar compact no matter how many pages there are.
        $pages = collect([1, $last, $cur - 1, $cur, $cur + 1])
          ->filter(fn ($p) => $p >= 1 && $p <= $last)
          ->unique()->sort()->values();
      @endphp
      <div class="pagination-bar">
        <span class="page-info">Showing {{ $runs->firstItem() }}–{{ $runs->lastItem() }} of {{ $runs->total() }} runs</span>

        @if ($runs->hasPages())
          <div class="page-nav">
            @if ($runs->onFirstPage())
              <span class="page-btn page-btn--disabled">&lsaquo;</span>
            @else
              <a href="{{ $runs->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
            @endif

            @php $prevPage = 0; @endphp
            @foreach ($pages as $p)
              @if ($prevPage && $p - $prevPage > 1)
                <span class="page-ellipsis">…</span>
              @endif
              @if ($p == $cur)
                <span class="page-btn page-btn--active">{{ $p }}</span>
              @else
                <a href="{{ $runs->url($p) }}" class="page-btn">{{ $p }}</a>
              @endif
              @php $prevPage = $p; @endphp
            @endforeach

            @if ($runs->hasMorePages())
              <a href="{{ $runs->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
            @else
              <span class="page-btn page-btn--disabled">&rsaquo;</span>
            @endif
          </div>
        @endif
      </div>
    @endif
  @endif

</div>


{{-- ── Day detail modal (calendar) — runs grouped by timeslot ── --}}
<div class="modal-overlay" id="dayModal" onclick="closeDayOnOverlay(event)">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h2 class="modal-title" id="day_title">Runs</h2>
      <button class="modal-close" onclick="closeDay()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body" id="day_body" style="max-height:65vh;overflow-y:auto;"></div>
  </div>
</div>


{{-- ── Reschedule modal ── --}}
<div class="modal-overlay" id="rescheduleModal" onclick="closeRescheduleOnOverlay(event)">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <h2 class="modal-title">Reschedule Run</h2>
      <button class="modal-close" onclick="closeReschedule()" aria-label="Close">&times;</button>
    </div>
    <form method="POST" id="rescheduleForm" novalidate>
      @csrf
      @method('PATCH')
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 14px;">
          Move <strong id="reschedule_route" style="color:var(--text);"></strong> currently on
          <strong id="reschedule_current" style="color:var(--text);"></strong> to a new date.
        </p>
        <div class="field">
          <label for="reschedule_date">New Date</label>
          <input type="date" id="reschedule_date" name="run_date" min="{{ $today->toDateString() }}" required />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeReschedule()">Cancel</button>
        <button type="submit" class="btn-primary">Move Run</button>
      </div>
    </form>
  </div>
</div>

{{-- Hidden form for cancel / reactivate. --}}
<form method="POST" id="actionForm" style="display:none;">
  @csrf
  @method('PATCH')
</form>


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
                <span>{{ Str::substr($name, 0, 3) }}</span>
              </label>
            @endforeach
          </div>
        </div>

        @php $oldAddMonthlyDay = old('start_date') ? (int) Str::substr(old('start_date'), 8, 2) : null; @endphp
        <div class="field monthly-field" id="add_monthly_field" style="margin-top:14px;{{ old('frequency') === 'monthly' ? '' : 'display:none;' }}">
          <label>Runs monthly</label>
          <p class="monthly-note" id="add_monthly_note">
            @if ($oldAddMonthlyDay)
              Runs on day <strong>{{ $oldAddMonthlyDay }}</strong> of every month, based on the start date. Months without that day (e.g. the 31st) are skipped.
            @else
              Choose a start date — the schedule will run on that day of every month. Months without that day (e.g. the 31st) are skipped.
            @endif
          </p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" onchange="updateMonthlyDay(false)" required />
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
                <span>{{ Str::substr($name, 0, 3) }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="field monthly-field" id="edit_monthly_field" style="margin-top:14px;display:none;">
          <label>Runs monthly</label>
          <p class="monthly-note" id="edit_monthly_note">Choose a start date — the schedule will run on that day of every month. Months without that day (e.g. the 31st) are skipped.</p>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_start_date">Start Date</label>
            <input type="date" id="edit_start_date" name="start_date" onchange="updateMonthlyDay(true)" required />
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
/* ── Header: view toggle + add ── */
.sched-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.view-toggle { display:inline-flex; border:1px solid var(--border); border-radius:6px; overflow:hidden; background:var(--bg); }
.view-toggle__btn {
  display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; font-weight:600;
  color:var(--text-muted); text-decoration:none; background:transparent; transition:background .12s, color .12s;
}
.view-toggle__btn + .view-toggle__btn { border-left:1px solid var(--border); }
.view-toggle__btn:hover { color:var(--text); }
.view-toggle__btn.is-active { background:var(--accent); color:#fff; }

/* ── Filter bar ── */
.filter-bar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; padding:14px 16px; background:var(--bg); border-bottom:1px solid var(--border); }
.filter-field { display:flex; flex-direction:column; gap:4px; }
.filter-field label { font-size:11px; font-weight:600; letter-spacing:.03em; text-transform:uppercase; color:var(--text-muted); }
.filter-field select, .filter-field input {
  height:34px; padding:0 10px; font-size:13px; color:var(--text);
  background:var(--surface); border:1px solid var(--border); border-radius:6px; min-width:140px;
}
.filter-field select:focus, .filter-field input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-light); }
.filter-actions { display:flex; align-items:center; gap:8px; }
.btn-primary--sm { padding:8px 16px; font-size:13px; }

/* ── Calendar ── */
.cal-toolbar { display: flex; align-items: center; justify-content: center; gap: 18px; padding: 18px 16px 8px; }
.cal-title   { font-size: 15px; font-weight: 700; color: var(--text); min-width: 160px; text-align: center; }
.cal-nav {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border: 1px solid var(--border); border-radius: 4px;
  background: var(--surface); color: var(--text-muted); cursor: pointer; transition: background .12s, color .12s;
}
.cal-nav:hover { background: var(--bg); color: var(--text); }

.cal-summary { display: flex; align-items: center; gap: 10px; justify-content: center; font-size: 12px; color: var(--text-muted); padding: 0 16px 14px; }
.cal-summary strong { color: var(--text); }
.cal-summary__dot { opacity: .5; }
.cal-today-link { margin-left: 6px; color: var(--accent); font-weight: 600; text-decoration: none; }
.cal-today-link:hover { text-decoration: underline; }

.cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); padding: 0 16px; margin-bottom: 6px; }
.cal-weekdays span {
  text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .04em;
  text-transform: uppercase; color: var(--text-muted); padding: 4px 0;
}
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; padding: 0 16px 18px; }
.cal-cell {
  position: relative; min-height: 74px; border: 1px solid var(--border); border-radius: 6px;
  padding: 6px 8px; background: var(--surface); font-size: 12px; color: var(--text);
}
.cal-cell--blank { border: none; background: transparent; }
.cal-cell--muted { color: var(--text-muted); background: var(--bg); }
.cal-cell__num   { font-weight: 600; }
.cal-cell--today .cal-cell__num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; margin: -2px; border-radius: 50%;
  background: var(--warning); color: #fff;
}
.cal-cell--has { cursor: pointer; transition: border-color .12s, box-shadow .12s; }
.cal-cell--has:hover { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.cal-count {
  position: absolute; top: 6px; right: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 20px; height: 20px; padding: 0 6px; border-radius: 11px;
  background: var(--accent); color: #fff; font-size: 11px; font-weight: 700;
}
.cal-cell__meta { position: absolute; bottom: 6px; left: 8px; font-size: 10px; color: var(--text-muted); }
.cal-cell__meta--cancelled { color: var(--error); }

/* ── List view ── */
.run-list { padding: 8px 16px 4px; }
.run-day { margin-bottom: 18px; }
.run-day:last-child { margin-bottom: 8px; }
.run-day__head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.run-day__date { font-size:13px; font-weight:700; color:var(--text); }
.run-day__count { font-size:11px; font-weight:600; color:var(--text-muted); }
.run-slot { border:1px solid var(--border); border-radius:6px; margin-bottom:10px; overflow:hidden; }
.run-slot:last-child { margin-bottom:0; }
.run-slot__head { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg); border-bottom:1px solid var(--border); }
.run-slot__time { font-size:13px; font-weight:700; color:var(--text); }
.run-slot__count { font-size:11px; font-weight:600; color:var(--text-muted); }
.run-empty { text-align:center; color:var(--text-muted); padding:40px 16px; font-size:13px; }

/* Pagination: summary on the left, windowed page buttons on the right */
.pagination-bar { justify-content:space-between; flex-wrap:wrap; gap:8px 4px; }
.page-info { font-size:12px; color:var(--text-muted); }
.page-nav { display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap; }
.page-ellipsis { min-width:24px; height:30px; display:inline-flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:12px; }

/* shared run rows (calendar modal + list) */
.day-run { display: flex; align-items: center; gap: 12px; padding: 10px 12px; font-size: 12px; background:var(--surface); }
.day-run + .day-run { border-top: 1px solid var(--border); }
.day-run--cancelled .day-run__route { text-decoration: line-through; color: var(--text-muted); }
.day-run--past { background: var(--bg); }
.day-run__main { flex: 1 1 auto; min-width: 0; }
.day-run__route { font-weight: 600; color: var(--text); }
.day-run__sub { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-top: 4px; }
.day-run__sub span { display: inline-flex; align-items: center; gap: 5px; color: var(--text-muted); white-space: nowrap; }
.day-run__sub svg { flex-shrink: 0; opacity: .8; }
.day-run__actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

.day-tag { font-size: 10px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; border-radius: 10px; padding: 2px 8px; }
.day-tag--past { color: var(--text-muted); background: var(--surface); border: 1px solid var(--border); }
.day-tag--cancelled { color: var(--error); background: #fef2f2; border: 1px solid #fecaca; }

.day-btn {
  display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
  font-size: 11px; font-weight: 600; padding: 4px 9px; border-radius: 4px;
  border: 1px solid var(--border); background: var(--surface); color: var(--text);
  transition: background .12s, color .12s, border-color .12s;
}
.day-btn:hover { background: var(--bg); border-color: var(--accent); color: var(--accent); }
.day-btn--danger:hover { background: #fef2f2; border-color: #fecaca; color: var(--error); }

/* Day modal timeslot grouping */
.day-slot { border: 1px solid var(--border); border-radius: 6px; margin-bottom: 12px; overflow: hidden; }
.day-slot:last-child { margin-bottom: 0; }
.day-slot__head { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: var(--bg); border-bottom: 1px solid var(--border); }
.day-slot__time { font-size: 13px; font-weight: 700; color: var(--text); }
.day-slot__count { font-size: 11px; font-weight: 600; color: var(--text-muted); }
.day-empty { text-align: center; color: var(--text-muted); padding: 30px 0; font-size: 13px; }

/* ── CRUD form bits ── */
.status-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 600; letter-spacing: 0.03em; padding: 2px 8px; border-radius: 20px; border: 1px solid transparent; }
.status-badge--active    { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.status-badge--inactive  { color: var(--text-muted); background: var(--bg); border-color: var(--border); }

.toggle-wrap  { display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; }
.toggle-track { position:relative; width:36px; height:20px; flex-shrink:0; }
.toggle-track input { opacity:0; width:0; height:0; position:absolute; }
.toggle-knob  { position:absolute; inset:0; background:var(--border); border-radius:20px; transition:background 0.18s; cursor:pointer; }
.toggle-knob::before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform 0.18s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.toggle-track input:checked + .toggle-knob { background:var(--success); }
.toggle-track input:checked + .toggle-knob::before { transform:translateX(16px); }

.monthly-note { margin:6px 0 0; font-size:12px; line-height:1.5; color:var(--text-muted); }
.monthly-note strong { color:var(--text); }

.weekday-grid { display:flex; flex-wrap:wrap; gap:8px; }
.weekday-chip { position:relative; cursor:pointer; user-select:none; }
.weekday-chip input { position:absolute; opacity:0; width:0; height:0; }
.weekday-chip span {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:46px; padding:7px 10px; font-size:12px; font-weight:600;
  color:var(--text-muted); background:var(--bg);
  border:1px solid var(--border); border-radius:8px; transition:all 0.15s;
}
.weekday-chip input:checked + span { color:#fff; background:var(--accent); border-color:var(--accent); }
.weekday-chip input:focus-visible + span { box-shadow:0 0 0 2px var(--accent-light); }
</style>


<script>
  const SCHEDULES = {{ Illuminate\Support\Js::from($scheduleData) }};
  const CAL_DATA  = {{ Illuminate\Support\Js::from($byDate) }};
  const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const SCHEDULE_BASE = '{{ url('panel/schedules') }}';

  const dayModal        = document.getElementById('dayModal');
  const dayBody         = document.getElementById('day_body');
  const rescheduleModal = document.getElementById('rescheduleModal');
  const rescheduleForm  = document.getElementById('rescheduleForm');

  function prettyDate(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return `${String(d).padStart(2, '0')} ${MONTHS[m - 1]} ${y}`;
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }

  const BUS_ICON = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
  const DRIVER_ICON = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/></svg>';

  // ─── Calendar day modal ───────────────────────────────────
  function openDay(iso) {
    const runs = CAL_DATA[iso] || [];
    const pretty = prettyDate(iso);
    document.getElementById('day_title').textContent = pretty;

    if (!runs.length) {
      dayBody.innerHTML = '<div class="day-empty">No runs scheduled.</div>';
    } else {
      const slots = {};
      runs.forEach(r => {
        const key = `${r.departure}–${r.arrival}`;
        (slots[key] ||= []).push(r);
      });

      dayBody.innerHTML = Object.keys(slots).map(key => {
        const items = slots[key];
        const rows = items.map(r => renderRun(r, iso, pretty)).join('');
        return `
          <div class="day-slot">
            <div class="day-slot__head">
              <span class="day-slot__time">${escapeHtml(key)}</span>
              <span class="day-slot__count">${items.length} ${items.length === 1 ? 'bus' : 'buses'}</span>
            </div>
            ${rows}
          </div>`;
      }).join('');
    }

    dayModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  // A run is past once its departure instant has elapsed — checked against the
  // live clock so buttons disappear even if the page was opened earlier.
  function isRunPast(r) {
    return r.departsAt ? Date.parse(r.departsAt) <= Date.now() : !!r.past;
  }

  function renderRun(r, iso, pretty) {
    const past = isRunPast(r);
    let actions;
    if (past) {
      // A departed run is locked — no reschedule, cancel, or schedule edit.
      actions = `<span class="day-tag day-tag--past">Past</span>`;
    } else {
      if (r.cancelled) {
        actions = `<span class="day-tag day-tag--cancelled">Cancelled</span>
          <button class="day-btn" data-act="reactivate" data-sid="${r.scheduleId}" data-rid="${r.id}" data-pretty="${escapeHtml(pretty)}">Reactivate</button>`;
      } else {
        actions = `
          <button class="day-btn" data-act="reschedule" data-sid="${r.scheduleId}" data-rid="${r.id}" data-iso="${iso}" data-route="${escapeHtml(r.route)}" data-pretty="${escapeHtml(pretty)}">Reschedule</button>
          <button class="day-btn day-btn--danger" data-act="cancel" data-sid="${r.scheduleId}" data-rid="${r.id}" data-pretty="${escapeHtml(pretty)}">Cancel</button>`;
      }
      actions += `<button class="day-btn" data-act="edit" data-sid="${r.scheduleId}" title="Edit recurring schedule">Edit</button>`;
    }

    return `
      <div class="day-run ${r.cancelled ? 'day-run--cancelled' : ''} ${past ? 'day-run--past' : ''}">
        <div class="day-run__main">
          <div class="day-run__route">${escapeHtml(r.route)}</div>
          <div class="day-run__sub">
            <span>${BUS_ICON}${escapeHtml(r.bus)}</span>
            <span>${DRIVER_ICON}${escapeHtml(r.driver)}</span>
          </div>
        </div>
        <div class="day-run__actions">${actions}</div>
      </div>`;
  }

  dayBody.addEventListener('click', e => {
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    const { act, sid, rid, iso, route, pretty } = btn.dataset;
    if (act === 'reschedule')      openReschedule(sid, rid, iso, route, pretty);
    else if (act === 'cancel')     cancelRun(sid, rid, pretty);
    else if (act === 'reactivate') reactivateRun(sid, rid, pretty);
    else if (act === 'edit')       { closeDay(); openEditModal(SCHEDULES[sid]); }
  });

  // ─── Run actions (shared by calendar modal + list view) ───
  function runUrl(sid, rid, suffix = '') {
    return `${SCHEDULE_BASE}/${sid}/runs/${rid}${suffix}`;
  }

  function openReschedule(sid, rid, iso, route, pretty) {
    rescheduleForm.action = runUrl(sid, rid);
    document.getElementById('reschedule_route').textContent = route;
    document.getElementById('reschedule_current').textContent = pretty;
    document.getElementById('reschedule_date').value = iso;
    closeDay();
    rescheduleModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('reschedule_date')?.focus());
  }
  function closeReschedule() {
    rescheduleModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  function closeRescheduleOnOverlay(e) { if (e.target === rescheduleModal) closeReschedule(); }

  function cancelRun(sid, rid, pretty) {
    if (!confirm(`Cancel the run on ${pretty}? You can reactivate it later.`)) return;
    submitAction(runUrl(sid, rid, '/cancel'));
  }
  function reactivateRun(sid, rid, pretty) {
    if (!confirm(`Reactivate the run on ${pretty}?`)) return;
    submitAction(runUrl(sid, rid, '/reactivate'));
  }
  function submitAction(action) {
    const form = document.getElementById('actionForm');
    form.action = action;
    form.submit();
  }

  function closeDay() {
    dayModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  function closeDayOnOverlay(e) { if (e.target === dayModal) closeDay(); }


  // ─── Frequency → weekday picker / monthly hint ────────────
  function toggleDays(which) {
    const isEdit = which === 'edit';
    const freq = document.getElementById(isEdit ? 'edit_frequency' : 'frequency').value;

    const daysField = document.getElementById(isEdit ? 'edit_days_field' : 'add_days_field');
    daysField.style.display = freq === 'weekly' ? '' : 'none';
    if (freq !== 'weekly') {
      daysField.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
    }

    const monthlyField = document.getElementById(isEdit ? 'edit_monthly_field' : 'add_monthly_field');
    monthlyField.style.display = freq === 'monthly' ? '' : 'none';
    if (freq === 'monthly') updateMonthlyDay(isEdit);
  }

  // Reflect the start date's day-of-month in the monthly hint.
  function updateMonthlyDay(isEdit) {
    const sd = document.getElementById(isEdit ? 'edit_start_date' : 'start_date').value;
    const note = document.getElementById(isEdit ? 'edit_monthly_note' : 'add_monthly_note');
    if (!note) return;
    const day = sd ? parseInt(sd.split('-')[2], 10) : null;
    note.innerHTML = day
      ? `Runs on day <strong>${day}</strong> of every month, based on the start date. Months without that day (e.g. the 31st) are skipped.`
      : 'Choose a start date — the schedule will run on that day of every month. Months without that day (e.g. the 31st) are skipped.';
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
  function closeAddModalOnOverlay(e) { if (e.target === addModal) closeAddModal(); }

  @if ($errors->any() && ! old('_edit_id'))
    document.addEventListener('DOMContentLoaded', openAddModal);
  @endif

  // ─── Edit Modal ───────────────────────────────────────────
  const editModal = document.getElementById('editScheduleModal');
  const editForm  = document.getElementById('editScheduleForm');

  function openEditModal(schedule) {
    if (!schedule) return;
    editForm.action = SCHEDULE_BASE + '/' + schedule.id;

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
  function closeEditModalOnOverlay(e) { if (e.target === editModal) closeEditModal(); }

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

  // ─── Global Escape handling ───────────────────────────────
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    if (rescheduleModal.classList.contains('is-open')) closeReschedule();
    else if (editModal.classList.contains('is-open'))  closeEditModal();
    else if (addModal.classList.contains('is-open'))   closeAddModal();
    else closeDay();
  });
</script>

@endsection
