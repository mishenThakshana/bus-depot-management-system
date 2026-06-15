@extends('layouts.panel')

@section('title', 'My Schedule')
@section('page-label', 'My Schedule')

@section('content')

@php
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Str;

  $baseParams = request()->except(['view', 'page', 'month']);

  $start         = $month->copy()->startOfMonth();
  $daysInMonth   = $month->daysInMonth;
  $leadingBlanks = ($start->dayOfWeek + 6) % 7; // Monday-first grid
  $todayIso      = $today->toDateString();

  $totalRuns  = $byDate->flatten(1)->reject(fn ($r) => $r['cancelled'])->count();
  $activeDays = $byDate->filter(fn ($day) => $day->contains(fn ($r) => ! $r['cancelled']))->count();

  $listGroups = $runs->getCollection()
    ->groupBy(fn ($r) => $r->run_date->toDateString())
    ->map(fn ($day) => $day->groupBy(fn ($r) =>
      Str::substr($r->schedule->departure_time, 0, 5) . '–' . Str::substr($r->schedule->arrival_time, 0, 5)));
@endphp

<div class="page-header">
  <h1 class="page-title">My Schedule</h1>
  <p class="page-subtitle">Your assigned runs, {{ $driver->name }}. This view is read-only — contact a supervisor for changes.</p>
  <div class="tracking-status" id="tracking-status" data-state="{{ $hasActiveRun ? 'connecting' : 'off' }}">
    <span class="tracking-status__dot"></span>
    <span class="tracking-status__label">{{ $hasActiveRun ? 'Preparing to share your location…' : 'No active run — location sharing is off' }}</span>
  </div>
</div>

<div class="table-wrapper">

  <div class="sched-header">
    <div class="view-toggle">
      <a href="{{ route('panel.my-schedule', array_merge($baseParams, ['view' => 'calendar'])) }}"
         class="view-toggle__btn {{ $view === 'calendar' ? 'is-active' : '' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Calendar
      </a>
      <a href="{{ route('panel.my-schedule', array_merge($baseParams, ['view' => 'list'])) }}"
         class="view-toggle__btn {{ $view === 'list' ? 'is-active' : '' }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        List
      </a>
    </div>
  </div>

  {{-- ── Filters ── --}}
  <form method="GET" action="{{ route('panel.my-schedule') }}" class="list-filter">
    <input type="hidden" name="view" value="{{ $view }}" />
    <div class="ff"><label>Date from</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" /></div>
    <div class="ff"><label>Date to</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" /></div>
    <div class="ff"><label>Timeslot from</label><input type="time" name="time_from" value="{{ $filters['time_from'] }}" /></div>
    <div class="ff"><label>Timeslot to</label><input type="time" name="time_to" value="{{ $filters['time_to'] }}" /></div>
    <div class="actions">
      <button type="submit" class="btn-primary">Apply</button>
      @if ($hasFilters)
        <a href="{{ route('panel.my-schedule', ['view' => $view]) }}" class="btn-ghost btn-ghost--sm">Clear</a>
      @endif
    </div>
  </form>


  {{-- ════════════════ CALENDAR VIEW ════════════════ --}}
  @if ($view === 'calendar')
    <div class="cal-toolbar">
      <a href="{{ route('panel.my-schedule', array_merge($baseParams, ['view' => 'calendar', 'month' => $prevMonth])) }}" class="cal-nav" aria-label="Previous month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <span class="cal-title">{{ $month->format('F Y') }}</span>
      <a href="{{ route('panel.my-schedule', array_merge($baseParams, ['view' => 'calendar', 'month' => $nextMonth])) }}" class="cal-nav" aria-label="Next month">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>

    <div class="cal-summary">
      <span><strong>{{ $totalRuns }}</strong> runs</span>
      <span class="cal-summary__dot">•</span>
      <span><strong>{{ $activeDays }}</strong> operating {{ Str::plural('day', $activeDays) }}</span>
      @unless ($month->isSameMonth($today))
        <a href="{{ route('panel.my-schedule', array_merge($baseParams, ['view' => 'calendar'])) }}" class="cal-today-link">Jump to today</a>
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
          $dayCarbon = Carbon::parse($date);
          $dayActive = $slots->flatten(1)->reject(fn ($r) => $r->isCancelled())->count();
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
                <span class="run-slot__count">{{ $slotRuns->count() }} {{ Str::plural('run', $slotRuns->count()) }}</span>
              </div>

              @foreach ($slotRuns as $run)
                @php $past = $run->isPast(); @endphp
                <div class="day-run {{ $run->isCancelled() ? 'day-run--cancelled' : '' }} {{ $past ? 'day-run--past' : '' }}">
                  <div class="day-run__main">
                    <div class="day-run__route">{{ $run->schedule?->route?->name ?? '—' }}</div>
                    <div class="day-run__sub">
                      <span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        {{ $run->schedule?->bus?->registration_number ?? '—' }}
                      </span>
                    </div>
                  </div>
                  <div class="day-run__actions">
                    @if ($past)
                      <span class="day-tag day-tag--past">Past</span>
                    @elseif ($run->isCancelled())
                      <span class="day-tag day-tag--cancelled">Cancelled</span>
                    @else
                      <span class="day-tag day-tag--ok">Scheduled</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      @empty
        <div class="run-empty">No runs {{ $hasFilters ? 'match these filters' : 'assigned to you yet' }}.</div>
      @endforelse
    </div>

    @if ($runs->total() > 0)
      @php
        $last  = $runs->lastPage();
        $cur   = $runs->currentPage();
        $pages = collect([1, $last, $cur - 1, $cur, $cur + 1])
          ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
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
              @if ($prevPage && $p - $prevPage > 1)<span class="page-ellipsis">…</span>@endif
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


{{-- ── Day detail modal (read-only) ── --}}
<div class="modal-overlay" id="dayModal" onclick="closeDayOnOverlay(event)">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h2 class="modal-title" id="day_title">Runs</h2>
      <button class="modal-close" onclick="closeDay()" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body" id="day_body" style="max-height:65vh;overflow-y:auto;"></div>
  </div>
</div>


<style>
.sched-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.view-toggle { display:inline-flex; border:1px solid var(--border); border-radius:6px; overflow:hidden; background:var(--bg); }
.view-toggle__btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; font-weight:600; color:var(--text-muted); text-decoration:none; transition:background .12s, color .12s; }
.view-toggle__btn + .view-toggle__btn { border-left:1px solid var(--border); }
.view-toggle__btn:hover { color:var(--text); }
.view-toggle__btn.is-active { background:var(--accent); color:#fff; }

.cal-toolbar { display:flex; align-items:center; justify-content:center; gap:18px; padding:18px 16px 8px; }
.cal-title { font-size:15px; font-weight:700; color:var(--text); min-width:160px; text-align:center; }
.cal-nav { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border:1px solid var(--border); border-radius:4px; background:var(--surface); color:var(--text-muted); cursor:pointer; transition:background .12s, color .12s; }
.cal-nav:hover { background:var(--bg); color:var(--text); }
.cal-summary { display:flex; align-items:center; gap:10px; justify-content:center; font-size:12px; color:var(--text-muted); padding:0 16px 14px; }
.cal-summary strong { color:var(--text); }
.cal-summary__dot { opacity:.5; }
.cal-today-link { margin-left:6px; color:var(--accent); font-weight:600; text-decoration:none; }
.cal-today-link:hover { text-decoration:underline; }
.cal-weekdays { display:grid; grid-template-columns:repeat(7, 1fr); padding:0 16px; margin-bottom:6px; }
.cal-weekdays span { text-align:center; font-size:11px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--text-muted); padding:4px 0; }
.cal-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:6px; padding:0 16px 18px; }
.cal-cell { position:relative; min-height:74px; border:1px solid var(--border); border-radius:6px; padding:6px 8px; background:var(--surface); font-size:12px; color:var(--text); }
.cal-cell--blank { border:none; background:transparent; }
.cal-cell--muted { color:var(--text-muted); background:var(--bg); }
.cal-cell__num { font-weight:600; }
.cal-cell--today .cal-cell__num { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; margin:-2px; border-radius:50%; background:var(--warning); color:#fff; }
.cal-cell--has { cursor:pointer; transition:border-color .12s, box-shadow .12s; }
.cal-cell--has:hover { border-color:var(--accent); box-shadow:0 0 0 1px var(--accent); }
.cal-count { position:absolute; top:6px; right:8px; display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:20px; padding:0 6px; border-radius:11px; background:var(--accent); color:#fff; font-size:11px; font-weight:700; }
.cal-cell__meta { position:absolute; bottom:6px; left:8px; font-size:10px; color:var(--text-muted); }
.cal-cell__meta--cancelled { color:var(--error); }

.run-list { padding:8px 16px 4px; }
.run-day { margin-bottom:18px; }
.run-day:last-child { margin-bottom:8px; }
.run-day__head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.run-day__date { font-size:13px; font-weight:700; color:var(--text); }
.run-day__count { font-size:11px; font-weight:600; color:var(--text-muted); }
.run-slot { border:1px solid var(--border); border-radius:6px; margin-bottom:10px; overflow:hidden; }
.run-slot:last-child { margin-bottom:0; }
.run-slot__head { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg); border-bottom:1px solid var(--border); }
.run-slot__time { font-size:13px; font-weight:700; color:var(--text); }
.run-slot__count { font-size:11px; font-weight:600; color:var(--text-muted); }
.run-empty { text-align:center; color:var(--text-muted); padding:40px 16px; font-size:13px; }

.day-run { display:flex; align-items:center; gap:12px; padding:10px 12px; font-size:12px; background:var(--surface); }
.day-run + .day-run { border-top:1px solid var(--border); }
.day-run--cancelled .day-run__route { text-decoration:line-through; color:var(--text-muted); }
.day-run--past { background:var(--bg); }
.day-run__main { flex:1 1 auto; min-width:0; }
.day-run__route { font-weight:600; color:var(--text); }
.day-run__sub { display:flex; flex-wrap:wrap; gap:4px 14px; margin-top:4px; }
.day-run__sub span { display:inline-flex; align-items:center; gap:5px; color:var(--text-muted); white-space:nowrap; }
.day-run__sub svg { flex-shrink:0; opacity:.8; }
.day-run__actions { display:flex; align-items:center; gap:6px; flex-shrink:0; }
.day-tag { font-size:10px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; border-radius:10px; padding:2px 8px; }
.day-tag--past { color:var(--text-muted); background:var(--surface); border:1px solid var(--border); }
.day-tag--cancelled { color:var(--error); background:#fef2f2; border:1px solid #fecaca; }
.day-tag--ok { color:var(--success); background:#f0fdf4; border:1px solid #bbf7d0; }

.day-slot { border:1px solid var(--border); border-radius:6px; margin-bottom:12px; overflow:hidden; }
.day-slot:last-child { margin-bottom:0; }
.day-slot__head { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg); border-bottom:1px solid var(--border); }
.day-slot__time { font-size:13px; font-weight:700; color:var(--text); }
.day-slot__count { font-size:11px; font-weight:600; color:var(--text-muted); }
.day-empty { text-align:center; color:var(--text-muted); padding:30px 0; font-size:13px; }

.pagination-bar { justify-content:space-between; flex-wrap:wrap; gap:8px 4px; }
.page-info { font-size:12px; color:var(--text-muted); }
.page-nav { display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap; }
.page-ellipsis { min-width:24px; height:30px; display:inline-flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:12px; }
</style>


<script>
  const CAL_DATA = {{ Illuminate\Support\Js::from($byDate) }};
  const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const dayModal = document.getElementById('dayModal');
  const dayBody = document.getElementById('day_body');

  function prettyDate(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return `${String(d).padStart(2, '0')} ${MONTHS[m - 1]} ${y}`;
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  }
  const BUS_ICON = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';

  function openDay(iso) {
    const runs = CAL_DATA[iso] || [];
    document.getElementById('day_title').textContent = prettyDate(iso);

    if (!runs.length) {
      dayBody.innerHTML = '<div class="day-empty">No runs scheduled.</div>';
    } else {
      const slots = {};
      runs.forEach(r => { const key = `${r.departure}–${r.arrival}`; (slots[key] ||= []).push(r); });
      dayBody.innerHTML = Object.keys(slots).map(key => {
        const rows = slots[key].map(renderRun).join('');
        return `<div class="day-slot"><div class="day-slot__head"><span class="day-slot__time">${escapeHtml(key)}</span><span class="day-slot__count">${slots[key].length} ${slots[key].length === 1 ? 'run' : 'runs'}</span></div>${rows}</div>`;
      }).join('');
    }

    dayModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function renderRun(r) {
    let tag;
    if (r.past) tag = '<span class="day-tag day-tag--past">Past</span>';
    else if (r.cancelled) tag = '<span class="day-tag day-tag--cancelled">Cancelled</span>';
    else tag = '<span class="day-tag day-tag--ok">Scheduled</span>';

    return `
      <div class="day-run ${r.cancelled ? 'day-run--cancelled' : ''} ${r.past ? 'day-run--past' : ''}">
        <div class="day-run__main">
          <div class="day-run__route">${escapeHtml(r.route)}</div>
          <div class="day-run__sub"><span>${BUS_ICON}${escapeHtml(r.bus)}</span></div>
        </div>
        <div class="day-run__actions">${tag}</div>
      </div>`;
  }

  function closeDay() {
    dayModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }
  function closeDayOnOverlay(e) { if (e.target === dayModal) closeDay(); }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDay(); });
</script>

@if ($hasActiveRun)
<script>
  // A run is live now — start sharing this browser's location to the server.
  document.addEventListener('DOMContentLoaded', () => window.initDriverTracking());
</script>
@endif

@endsection
