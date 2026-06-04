@extends('layouts.panel')

@section('title', 'Schedule')
@section('page-label', 'Schedules')

@section('content')

@php
  $today = \Illuminate\Support\Carbon::today();
@endphp

<div class="page-header">
  <a href="{{ route('panel.schedules') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to schedules
  </a>
  <h1 class="page-title">Scheduled Dates</h1>
  <p class="page-subtitle">Cancel or reschedule individual dates for this schedule. Past dates are locked.</p>
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

{{-- ── Schedule summary ── --}}
<div class="summary-card">
  <div class="summary-item">
    <span class="summary-label">Route</span>
    <span class="summary-value">{{ $schedule->route?->name ?? '—' }}</span>
  </div>
  <div class="summary-item">
    <span class="summary-label">Bus</span>
    <span class="summary-value">{{ $schedule->bus?->registration_number ?? '—' }}</span>
  </div>
  <div class="summary-item">
    <span class="summary-label">Driver</span>
    <span class="summary-value">{{ $schedule->driver?->name ?? '—' }}</span>
  </div>
  <div class="summary-item">
    <span class="summary-label">Window</span>
    <span class="summary-value">{{ \Illuminate\Support\Str::substr($schedule->departure_time, 0, 5) }} &ndash; {{ \Illuminate\Support\Str::substr($schedule->arrival_time, 0, 5) }}</span>
  </div>
  <div class="summary-item">
    <span class="summary-label">Status</span>
    <span class="summary-value">
      @if ($schedule->is_active)
        <span class="status-badge status-badge--active">Active</span>
      @else
        <span class="status-badge status-badge--inactive">Inactive</span>
      @endif
    </span>
  </div>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Scheduled Dates <span class="table-count">({{ $runs->total() }})</span></span>
    <div class="view-toggle">
      <button type="button" class="view-toggle__btn is-active" id="calendarViewBtn" onclick="switchView('calendar')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Calendar
      </button>
      <button type="button" class="view-toggle__btn" id="listViewBtn" onclick="switchView('list')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        List
      </button>
    </div>
  </div>

  {{-- ── Calendar view ── --}}
  <div id="calendarView">
    <div class="cal-toolbar">
      <button type="button" class="cal-nav" onclick="calShift(-1)" aria-label="Previous month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <span class="cal-title" id="calTitle"></span>
      <button type="button" class="cal-nav" onclick="calShift(1)" aria-label="Next month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
    <div class="cal-weekdays">
      <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
    </div>
    <div class="cal-grid" id="calGrid"></div>
  </div>

  {{-- ── List view ── --}}
  <div id="listView" style="display:none;">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Day</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($runs as $run)
          @php
            $isPast      = $run->run_date->lt($today);
            $isToday     = $run->run_date->isSameDay($today);
            $isCancelled = $run->isCancelled();
            $pretty      = $run->run_date->format('d M Y');
          @endphp
          <tr style="{{ $isPast || $isCancelled ? 'opacity:0.6;' : '' }}">
            <td style="color:var(--text-muted);font-size:12px;">{{ $runs->firstItem() + $loop->index }}</td>
            <td style="font-weight:600;{{ $isCancelled ? 'text-decoration:line-through;' : '' }}">{{ $pretty }}</td>
            <td style="color:var(--text-muted);">{{ $run->run_date->format('l') }}</td>
            <td>
              @if ($isCancelled)
                <span class="status-badge status-badge--cancelled">Cancelled</span>
              @elseif ($isPast)
                <span class="status-badge status-badge--inactive">Past</span>
              @elseif ($isToday)
                <span class="status-badge status-badge--today">Today</span>
              @else
                <span class="status-badge status-badge--active">Upcoming</span>
              @endif
            </td>
            <td style="white-space:nowrap;text-align:right;">
              @if ($isPast)
                <span style="color:var(--text-muted);font-size:12px;">—</span>
              @elseif ($isCancelled)
                <button
                  class="btn-ghost btn-ghost--sm"
                  onclick="reactivateRun({{ $run->id }}, '{{ $pretty }}')"
                >Reactivate</button>
              @else
                <button
                  class="btn-ghost btn-ghost--sm"
                  onclick="openRescheduleModal({{ $run->id }}, '{{ $run->run_date->format('Y-m-d') }}', '{{ $pretty }}')"
                >Reschedule</button>
                <button
                  class="btn-ghost btn-ghost--sm btn-ghost--danger"
                  onclick="cancelRun({{ $run->id }}, '{{ $pretty }}')"
                >Cancel</button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
              This schedule has no scheduled dates.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    @if ($runs->hasPages())
      <div class="pagination-bar">
        @if ($runs->onFirstPage())
          <span class="page-btn page-btn--disabled">&lsaquo;</span>
        @else
          <a href="{{ $runs->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
        @endif
        @foreach ($runs->getUrlRange(1, $runs->lastPage()) as $page => $url)
          @if ($page == $runs->currentPage())
            <span class="page-btn page-btn--active">{{ $page }}</span>
          @else
            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
          @endif
        @endforeach
        @if ($runs->hasMorePages())
          <a href="{{ $runs->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
        @else
          <span class="page-btn page-btn--disabled">&rsaquo;</span>
        @endif
      </div>
    @endif
  </div>
</div>


{{-- ── Run action chooser (opened from a calendar day) ── --}}
<div class="modal-overlay" id="actionModal" onclick="closeActionOnOverlay(event)">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <h2 class="modal-title">Scheduled Date</h2>
      <button class="modal-close" onclick="closeActionModal()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;">
        <strong id="action_date" style="color:var(--text);"></strong>
      </p>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn-ghost" style="flex:1;justify-content:center;" id="action_reschedule">Reschedule</button>
        <button type="button" class="btn-ghost btn-ghost--danger" style="flex:1;justify-content:center;" id="action_cancel">Cancel run</button>
        <button type="button" class="btn-ghost" style="flex:1;justify-content:center;" id="action_reactivate">Reactivate</button>
      </div>
    </div>
  </div>
</div>


{{-- ── Reschedule Modal ── --}}
<div class="modal-overlay" id="rescheduleModal" onclick="closeRescheduleOnOverlay(event)">
  <div class="modal" style="max-width:420px;">
    <div class="modal-header">
      <h2 class="modal-title">Reschedule Run</h2>
      <button class="modal-close" onclick="closeRescheduleModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" id="rescheduleForm" novalidate>
      @csrf
      @method('PATCH')
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin:0 0 14px;">
          Move the run currently on <strong id="reschedule_current" style="color:var(--text);"></strong> to a new date.
        </p>
        <div class="field">
          <label for="reschedule_date">New Date</label>
          <input type="date" id="reschedule_date" name="run_date" min="{{ $today->format('Y-m-d') }}" required />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeRescheduleModal()">Cancel</button>
        <button type="submit" class="btn-primary">Move Run</button>
      </div>
    </form>
  </div>
</div>

{{-- Hidden form used to submit cancel / reactivate for any run. --}}
<form method="POST" id="actionForm" style="display:none;">
  @csrf
  @method('PATCH')
</form>


<style>
.summary-card {
  display: flex;
  flex-wrap: wrap;
  gap: 28px;
  padding: 16px 20px;
  margin-bottom: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 4px;
}
.summary-item  { display: flex; flex-direction: column; gap: 4px; }
.summary-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--text-muted); }
.summary-value { font-size: 14px; font-weight: 600; color: var(--text); }

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
.status-badge--active   { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.status-badge--inactive { color: var(--text-muted); background: var(--bg); border-color: var(--border); }
.status-badge--today    { color: var(--warning); background: #fffbeb; border-color: #fde68a; }
.status-badge--cancelled { color: var(--error); background: #fef2f2; border-color: #fecaca; }

.btn-ghost--danger        { color: var(--error); }
.btn-ghost--danger:hover  { background: #fef2f2; color: var(--error); }

/* View toggle */
.view-toggle { display: inline-flex; border: 1px solid var(--border); border-radius: 4px; overflow: hidden; }
.view-toggle__btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 11px; font-size: 12px; font-weight: 500;
  color: var(--text-muted); background: var(--surface); border: none; cursor: pointer;
  transition: background .12s, color .12s;
}
.view-toggle__btn + .view-toggle__btn { border-left: 1px solid var(--border); }
.view-toggle__btn:hover    { color: var(--text); background: var(--bg); }
.view-toggle__btn.is-active { color: var(--accent); background: var(--accent-light); }

/* Calendar */
.cal-toolbar { display: flex; align-items: center; justify-content: center; gap: 18px; padding: 18px 16px 12px; }
.cal-title   { font-size: 14px; font-weight: 600; color: var(--text); min-width: 150px; text-align: center; }
.cal-nav {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border: 1px solid var(--border); border-radius: 4px;
  background: var(--surface); color: var(--text-muted); cursor: pointer; transition: background .12s, color .12s;
}
.cal-nav:hover { background: var(--bg); color: var(--text); }

.cal-weekdays {
  display: grid; grid-template-columns: repeat(7, 1fr);
  padding: 0 16px; margin-bottom: 6px;
}
.cal-weekdays span {
  text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .04em;
  text-transform: uppercase; color: var(--text-muted); padding: 4px 0;
}
.cal-grid {
  display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; padding: 0 16px 18px;
}
.cal-cell {
  position: relative; min-height: 60px; border: 1px solid var(--border); border-radius: 6px;
  padding: 6px 8px; background: var(--surface); font-size: 12px; color: var(--text);
}
.cal-cell--blank { border: none; background: transparent; }
.cal-cell--muted { color: var(--text-muted); background: var(--bg); }
.cal-cell__num { font-weight: 600; }
.cal-cell--today .cal-cell__num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; margin: -2px; border-radius: 50%;
  background: var(--warning); color: #fff;
}
.cal-cell--run { cursor: default; }
.cal-cell--actionable { cursor: pointer; }
.cal-cell--actionable:hover { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
.cal-run {
  display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
  padding: 2px 7px; border-radius: 12px; font-size: 11px; font-weight: 600;
  border: 1px solid transparent; max-width: 100%;
}
.cal-run--upcoming { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.cal-run--today    { color: var(--warning); background: #fffbeb; border-color: #fde68a; }
.cal-run--past     { color: var(--text-muted); background: var(--bg); border-color: var(--border); }
.cal-run--cancelled { color: var(--error); background: #fef2f2; border-color: #fecaca; text-decoration: line-through; }
</style>


<script>
  const CAL_RUNS = {{ Illuminate\Support\Js::from($calendar) }};
  const TODAY    = '{{ $today->format('Y-m-d') }}';
  const RUN_BASE = '{{ url('panel/schedules/' . $schedule->id . '/runs') }}';

  const runsByDate = {};
  CAL_RUNS.forEach(r => { runsByDate[r.date] = r; });

  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  // ─── View toggle ──────────────────────────────────────────
  function switchView(view) {
    const isCal = view === 'calendar';
    document.getElementById('calendarView').style.display = isCal ? '' : 'none';
    document.getElementById('listView').style.display     = isCal ? 'none' : '';
    document.getElementById('calendarViewBtn').classList.toggle('is-active', isCal);
    document.getElementById('listViewBtn').classList.toggle('is-active', !isCal);
  }

  // ─── Calendar ─────────────────────────────────────────────
  // Start on the month of the first upcoming run, else the earliest run, else today.
  let calCursor = (() => {
    const upcoming = CAL_RUNS.find(r => !r.past);
    const anchor = upcoming?.date || CAL_RUNS[0]?.date || TODAY;
    const [y, m] = anchor.split('-').map(Number);
    return { year: y, month: m - 1 };
  })();

  function pad(n) { return String(n).padStart(2, '0'); }

  function calShift(delta) {
    calCursor.month += delta;
    if (calCursor.month < 0)  { calCursor.month = 11; calCursor.year--; }
    if (calCursor.month > 11) { calCursor.month = 0;  calCursor.year++; }
    renderCalendar();
  }

  function renderCalendar() {
    const { year, month } = calCursor;
    document.getElementById('calTitle').textContent = `${MONTHS[month]} ${year}`;

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    // Monday-first leading blanks.
    const firstDow = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for (let i = 0; i < firstDow; i++) {
      const blank = document.createElement('div');
      blank.className = 'cal-cell cal-cell--blank';
      grid.appendChild(blank);
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const iso = `${year}-${pad(month + 1)}-${pad(day)}`;
      const run = runsByDate[iso];
      const isToday = iso === TODAY;
      const isPastDay = iso < TODAY;

      const cell = document.createElement('div');
      cell.className = 'cal-cell';
      if (isToday) cell.classList.add('cal-cell--today');
      else if (isPastDay && !run) cell.classList.add('cal-cell--muted');

      let html = `<span class="cal-cell__num">${day}</span>`;

      if (run) {
        cell.classList.add('cal-cell--run');
        let tone, label;
        if (run.cancelled)   { tone = 'cancelled'; label = 'Cancelled'; }
        else if (run.past)   { tone = 'past';      label = 'Run'; }
        else if (isToday)    { tone = 'today';     label = 'Today'; }
        else                 { tone = 'upcoming';  label = 'Run'; }
        html += `<div class="cal-run cal-run--${tone}">${label}</div>`;

        if (!run.past) {
          cell.classList.add('cal-cell--actionable');
          cell.onclick = () => openActionModal(run, iso, formatPretty(iso));
        }
      }

      cell.innerHTML = html;
      grid.appendChild(cell);
    }
  }

  function formatPretty(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return `${pad(d)} ${MONTHS[m - 1].slice(0, 3)} ${y}`;
  }

  // ─── Action chooser ───────────────────────────────────────
  const actionModal = document.getElementById('actionModal');

  function openActionModal(run, iso, pretty) {
    document.getElementById('action_date').textContent = pretty;

    const resBtn = document.getElementById('action_reschedule');
    const canBtn = document.getElementById('action_cancel');
    const reaBtn = document.getElementById('action_reactivate');

    if (run.cancelled) {
      resBtn.style.display = 'none';
      canBtn.style.display = 'none';
      reaBtn.style.display = '';
      reaBtn.onclick = () => { closeActionModal(); reactivateRun(run.id, pretty); };
    } else {
      resBtn.style.display = '';
      canBtn.style.display = '';
      reaBtn.style.display = 'none';
      resBtn.onclick = () => { closeActionModal(); openRescheduleModal(run.id, iso, pretty); };
      canBtn.onclick = () => { closeActionModal(); cancelRun(run.id, pretty); };
    }

    actionModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }
  function closeActionModal() {
    actionModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  function closeActionOnOverlay(e) { if (e.target === actionModal) closeActionModal(); }

  // ─── Reschedule ───────────────────────────────────────────
  const rescheduleModal = document.getElementById('rescheduleModal');
  const rescheduleForm  = document.getElementById('rescheduleForm');

  function openRescheduleModal(runId, isoDate, prettyDate) {
    rescheduleForm.action = `${RUN_BASE}/${runId}`;
    document.getElementById('reschedule_current').textContent = prettyDate;
    document.getElementById('reschedule_date').value = isoDate;
    rescheduleModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('reschedule_date')?.focus());
  }
  function closeRescheduleModal() {
    rescheduleModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  function closeRescheduleOnOverlay(e) { if (e.target === rescheduleModal) closeRescheduleModal(); }

  // ─── Cancel / Reactivate ──────────────────────────────────
  function cancelRun(runId, prettyDate) {
    if (!confirm(`Cancel the run on ${prettyDate}? You can reactivate it later.`)) return;
    submitAction(`${RUN_BASE}/${runId}/cancel`);
  }

  function reactivateRun(runId, prettyDate) {
    if (!confirm(`Reactivate the run on ${prettyDate}?`)) return;
    submitAction(`${RUN_BASE}/${runId}/reactivate`);
  }

  function submitAction(action) {
    const form = document.getElementById('actionForm');
    form.action = action;
    form.submit();
  }

  renderCalendar();
</script>

@endsection
