@extends('layouts.panel')

@section('title', 'Buses')
@section('page-label', 'Buses')

@section('content')

<div class="page-header">
  <h1 class="page-title">Buses</h1>
  <p class="page-subtitle">
    @if(auth()->user()->isAdmin())
      Manage the depot fleet — registration, type, capacity, and service status.
    @else
      View the depot fleet — registration, type, capacity, and service status.
    @endif
  </p>
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
    <span class="table-title">All Buses <span class="table-count">({{ $buses->total() }})</span></span>
    @if(auth()->user()->isAdmin())
    <button class="btn-primary" onclick="openAddModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Bus
    </button>
    @endif
  </div>

  <form method="GET" action="{{ route('panel.buses') }}" class="list-filter">
    <div class="ff ff--grow">
      <label>Search</label>
      <input type="text" name="search" value="{{ $search }}" placeholder="Registration number…" />
    </div>
    <div class="ff">
      <label>Vehicle type</label>
      <select name="type">
        <option value="">All types</option>
        @foreach (\App\Models\Bus::$vehicleTypes as $vt)
          <option value="{{ $vt }}" {{ $type === $vt ? 'selected' : '' }}>{{ $vt }}</option>
        @endforeach
      </select>
    </div>
    <div class="ff">
      <label>Status</label>
      <select name="status">
        <option value="">All</option>
        <option value="in" {{ $status === 'in' ? 'selected' : '' }}>In service</option>
        <option value="out" {{ $status === 'out' ? 'selected' : '' }}>Out of service</option>
      </select>
    </div>
    <div class="actions">
      <button type="submit" class="btn-primary">Apply</button>
      @if ($search !== '' || $type || $status)
        <a href="{{ route('panel.buses') }}" class="btn-ghost btn-ghost--sm">Clear</a>
      @endif
    </div>
  </form>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Registration No.</th>
        <th>Vehicle Type</th>
        <th>Seats</th>
        <th>Mileage (km)</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($buses as $bus)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $buses->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;letter-spacing:0.03em;">{{ $bus->registration_number }}</td>
          <td>{{ $bus->vehicle_type }}</td>
          <td style="color:var(--text-muted);">{{ $bus->seat_capacity }}</td>
          <td style="color:var(--text-muted);">{{ number_format($bus->current_mileage) }}</td>
          <td>
            @if ($bus->is_in_service)
              <span class="status-badge status-badge--active">In Service</span>
            @else
              <span class="status-badge status-badge--inactive">Out of Service</span>
            @endif
          </td>
          <td>
            @if(auth()->user()->isAdmin())
            <div style="display:flex;align-items:center;gap:8px;">
              <button
                class="btn-ghost btn-ghost--sm"
                onclick="openEditModal({{ json_encode([
                  'id'                  => $bus->id,
                  'registration_number' => $bus->registration_number,
                  'vehicle_type'        => $bus->vehicle_type,
                  'seat_capacity'       => $bus->seat_capacity,
                  'current_mileage'     => $bus->current_mileage,
                  'is_in_service'       => $bus->is_in_service,
                ]) }})"
              >Edit</button>
              <form method="POST" action="{{ route('panel.buses.destroy', $bus) }}" onsubmit="return confirm('Remove bus \'{{ addslashes($bus->registration_number) }}\' from the system?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-ghost btn-ghost--sm" style="color:var(--error);border-color:var(--error);">Remove</button>
              </form>
            </div>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            @if(auth()->user()->isAdmin())
              No buses found. Add one using the button above.
            @else
              No buses found.
            @endif
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($buses->hasPages())
    <div class="pagination-bar">
      @if ($buses->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $buses->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($buses->getUrlRange(1, $buses->lastPage()) as $page => $url)
        @if ($page == $buses->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($buses->hasMorePages())
        <a href="{{ $buses->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>


{{-- ── Add Bus Modal ── --}}
<div class="modal-overlay" id="addBusModal" onclick="closeAddModalOnOverlay(event)">
  <div class="modal" style="max-width:480px;">

    <div class="modal-header">
      <h2 class="modal-title">Add Bus</h2>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" action="{{ route('panel.buses.store') }}" novalidate id="addBusForm">
      @csrf

      <div class="modal-body">

        @if ($errors->any())
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
          <label for="registration_number">Registration Number</label>
          <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" placeholder="e.g. NB-1234" required autocomplete="off" style="text-transform:uppercase;" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="vehicle_type">Vehicle Type</label>
            <select id="vehicle_type" name="vehicle_type" required>
              <option value="" disabled {{ old('vehicle_type') ? '' : 'selected' }}>Select type…</option>
              @foreach (\App\Models\Bus::$vehicleTypes as $type)
                <option value="{{ $type }}" {{ old('vehicle_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="seat_capacity">Seat Capacity</label>
            <input type="number" id="seat_capacity" name="seat_capacity" value="{{ old('seat_capacity') }}" placeholder="e.g. 54" min="1" max="200" required />
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="current_mileage">Current Mileage (km)</label>
          <input type="number" id="current_mileage" name="current_mileage" value="{{ old('current_mileage', 0) }}" placeholder="e.g. 45000" min="0" required />
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn-primary">Add Bus</button>
      </div>
    </form>
  </div>
</div>


{{-- ── Edit Bus Modal ── --}}
<div class="modal-overlay" id="editBusModal" onclick="closeEditModalOnOverlay(event)">
  <div class="modal" style="max-width:480px;">

    <div class="modal-header">
      <h2 class="modal-title">Edit Bus</h2>
      <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" id="editBusForm" novalidate>
      @csrf
      @method('PATCH')

      <div class="modal-body">

        <div id="editErrors" style="display:none;" class="alert alert--error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <div id="editErrorList"></div>
        </div>

        <div class="field">
          <label for="edit_registration_number">Registration Number</label>
          <input type="text" id="edit_registration_number" name="registration_number" placeholder="e.g. NB-1234" required autocomplete="off" style="text-transform:uppercase;" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_vehicle_type">Vehicle Type</label>
            <select id="edit_vehicle_type" name="vehicle_type" required>
              @foreach (\App\Models\Bus::$vehicleTypes as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="edit_seat_capacity">Seat Capacity</label>
            <input type="number" id="edit_seat_capacity" name="seat_capacity" placeholder="e.g. 54" min="1" max="200" required />
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="edit_current_mileage">Current Mileage (km)</label>
          <input type="number" id="edit_current_mileage" name="current_mileage" placeholder="e.g. 45000" min="0" required />
        </div>

        <div style="margin-top:16px;">
          <label style="font-size:13px;font-weight:500;color:var(--text);display:block;margin-bottom:8px;">Service Status</label>
          <label class="toggle-wrap">
            <span class="toggle-track">
              <input type="checkbox" id="edit_is_in_service" name="is_in_service" value="1" onchange="document.getElementById('edit_service_label').textContent = this.checked ? 'In Service' : 'Out of Service'" />
              <span class="toggle-knob"></span>
            </span>
            <span id="edit_service_label" style="font-size:13px;color:var(--text);">In Service</span>
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
.status-badge--active   { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.status-badge--inactive { color: var(--text-muted); background: var(--bg); border-color: var(--border); }

.toggle-wrap  { display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; }
.toggle-track { position:relative; width:36px; height:20px; flex-shrink:0; }
.toggle-track input { opacity:0; width:0; height:0; position:absolute; }
.toggle-knob  { position:absolute; inset:0; background:var(--border); border-radius:20px; transition:background 0.18s; cursor:pointer; }
.toggle-knob::before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform 0.18s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.toggle-track input:checked + .toggle-knob { background:var(--success); }
.toggle-track input:checked + .toggle-knob::before { transform:translateX(16px); }
</style>


<script>
  // ─── Add Modal ────────────────────────────────────────────
  const addModal = document.getElementById('addBusModal');

  function openAddModal() {
    addModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('registration_number')?.focus());
  }

  function closeAddModal() {
    addModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeAddModalOnOverlay(e) {
    if (e.target === addModal) closeAddModal();
  }

  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', openAddModal);
  @endif


  // ─── Edit Modal ───────────────────────────────────────────
  const editModal = document.getElementById('editBusModal');
  const editForm  = document.getElementById('editBusForm');

  function openEditModal(bus) {
    editForm.action = '/panel/buses/' + bus.id;

    document.getElementById('edit_registration_number').value = bus.registration_number;
    document.getElementById('edit_vehicle_type').value        = bus.vehicle_type;
    document.getElementById('edit_seat_capacity').value       = bus.seat_capacity;
    document.getElementById('edit_current_mileage').value     = bus.current_mileage;

    const svcToggle = document.getElementById('edit_is_in_service');
    svcToggle.checked = !!bus.is_in_service;
    document.getElementById('edit_service_label').textContent = bus.is_in_service ? 'In Service' : 'Out of Service';

    document.getElementById('editErrors').style.display = 'none';

    editModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('edit_registration_number')?.focus());
  }

  function closeEditModal() {
    editModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeEditModalOnOverlay(e) {
    if (e.target === editModal) closeEditModal();
  }
</script>

@endsection
