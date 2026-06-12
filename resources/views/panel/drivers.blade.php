@extends('layouts.panel')

@section('title', 'Drivers')
@section('page-label', 'Drivers')

@section('content')

<div class="page-header">
  <h1 class="page-title">Drivers</h1>
  <p class="page-subtitle">
    @if(auth()->user()->isAdmin())
      Manage driver records, licences, and employment status.
    @else
      View driver records, licences, and employment status.
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
    <span class="table-title">All Drivers <span class="table-count">({{ $drivers->total() }})</span></span>
    @if(auth()->user()->isAdmin())
    <button class="btn-primary" onclick="openAddModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Driver
    </button>
    @endif
  </div>

  <form method="GET" action="{{ route('panel.drivers') }}" class="list-filter">
    <div class="ff ff--grow">
      <label>Search</label>
      <input type="text" name="search" value="{{ $search }}" placeholder="Name, NIC, licence, email or phone…" />
    </div>
    <div class="ff">
      <label>Licence</label>
      <select name="licence">
        <option value="">All</option>
        <option value="expired" {{ $licence === 'expired' ? 'selected' : '' }}>Expired</option>
        <option value="soon" {{ $licence === 'soon' ? 'selected' : '' }}>Expiring ≤ 60 days</option>
        <option value="valid" {{ $licence === 'valid' ? 'selected' : '' }}>Valid</option>
      </select>
    </div>
    <div class="actions">
      <button type="submit" class="btn-primary">Apply</button>
      @if ($search !== '' || $licence)
        <a href="{{ route('panel.drivers') }}" class="btn-ghost btn-ghost--sm">Clear</a>
      @endif
    </div>
  </form>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>NIC</th>
        <th>Licence No.</th>
        <th>Licence Expiry</th>
        <th>Phone</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($drivers as $driver)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $drivers->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $driver->name }}</td>
          <td style="color:var(--text-muted);">{{ $driver->email ?? '—' }}</td>
          <td style="color:var(--text-muted);letter-spacing:0.03em;">{{ $driver->nic }}</td>
          <td style="letter-spacing:0.03em;">{{ $driver->licence_number }}</td>
          <td style="color:{{ $driver->licence_expiry_date->isPast() ? 'var(--error)' : ($driver->licence_expiry_date->diffInDays(now()) <= 90 ? 'var(--warning,#b45309)' : 'var(--text-muted)') }};">
            {{ $driver->licence_expiry_date->format('d M Y') }}
          </td>
          <td style="color:var(--text-muted);">{{ $driver->phone_number }}</td>
          <td>
            @if ($driver->is_active)
              <span class="status-badge status-badge--active">Active</span>
            @else
              <span class="status-badge status-badge--inactive">Inactive</span>
            @endif
          </td>
          <td>
            @if(auth()->user()->isAdmin())
            <div style="display:flex;align-items:center;gap:8px;">
              <button
                class="btn-ghost btn-ghost--sm"
                onclick="openEditModal({{ json_encode([
                  'id'                  => $driver->id,
                  'name'                => $driver->name,
                  'email'               => $driver->email,
                  'nic'                 => $driver->nic,
                  'licence_number'      => $driver->licence_number,
                  'licence_expiry_date' => $driver->licence_expiry_date->format('Y-m-d'),
                  'phone_number'        => $driver->phone_number,
                  'is_active'           => $driver->is_active,
                ]) }})"
              >Edit</button>
              <form method="POST" action="{{ route('panel.drivers.toggle-active', $driver) }}">
                @csrf
                @method('PATCH')
                <button
                  type="submit"
                  class="btn-ghost btn-ghost--sm"
                  style="{{ $driver->is_active ? 'color:var(--error);border-color:var(--error);' : '' }}"
                  onclick="return confirm('{{ $driver->is_active ? 'Deactivate' : 'Activate' }} driver \'{{ addslashes($driver->name) }}\'?')"
                >{{ $driver->is_active ? 'Deactivate' : 'Activate' }}</button>
              </form>
            </div>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            @if(auth()->user()->isAdmin())
              No drivers found. Add one using the button above.
            @else
              No drivers found.
            @endif
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>


  @if ($drivers->hasPages())
    <div class="pagination-bar">
      @if ($drivers->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $drivers->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($drivers->getUrlRange(1, $drivers->lastPage()) as $page => $url)
        @if ($page == $drivers->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($drivers->hasMorePages())
        <a href="{{ $drivers->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>


{{-- ── Add Driver Modal ── --}}
<div class="modal-overlay" id="addDriverModal" onclick="closeAddModalOnOverlay(event)">
  <div class="modal" style="max-width:520px;">

    <div class="modal-header">
      <h2 class="modal-title">Add Driver</h2>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" action="{{ route('panel.drivers.store') }}" novalidate id="addDriverForm">
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
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Kamal Perera" required autocomplete="off" />
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="email">Email <span style="font-weight:400;color:var(--text-muted);">(a login account is created and emailed)</span></label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="e.g. kamal@depot.com" required autocomplete="off" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="nic">NIC Number</label>
            <input type="text" id="nic" name="nic" value="{{ old('nic') }}" placeholder="e.g. 901234567V" required autocomplete="off" />
          </div>
          <div class="field">
            <label for="phone_number">Phone Number</label>
            <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" placeholder="e.g. 0771234567" required autocomplete="off" />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="licence_number">Licence Number</label>
            <input type="text" id="licence_number" name="licence_number" value="{{ old('licence_number') }}" placeholder="e.g. B1234567" required autocomplete="off" />
          </div>
          <div class="field">
            <label for="licence_expiry_date">Licence Expiry Date</label>
            <input type="date" id="licence_expiry_date" name="licence_expiry_date" value="{{ old('licence_expiry_date') }}" required />
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn-primary">Add Driver</button>
      </div>
    </form>
  </div>
</div>


{{-- ── Edit Driver Modal ── --}}
<div class="modal-overlay" id="editDriverModal" onclick="closeEditModalOnOverlay(event)">
  <div class="modal" style="max-width:520px;">

    <div class="modal-header">
      <h2 class="modal-title">Edit Driver</h2>
      <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" id="editDriverForm" novalidate>
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
          <label for="edit_name">Full Name</label>
          <input type="text" id="edit_name" name="name" placeholder="e.g. Kamal Perera" required autocomplete="off" />
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="edit_email">Email</label>
          <input type="email" id="edit_email" name="email" placeholder="e.g. kamal@depot.com" required autocomplete="off" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_nic">NIC Number</label>
            <input type="text" id="edit_nic" name="nic" placeholder="e.g. 901234567V" required autocomplete="off" />
          </div>
          <div class="field">
            <label for="edit_phone_number">Phone Number</label>
            <input type="text" id="edit_phone_number" name="phone_number" placeholder="e.g. 0771234567" required autocomplete="off" />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_licence_number">Licence Number</label>
            <input type="text" id="edit_licence_number" name="licence_number" placeholder="e.g. B1234567" required autocomplete="off" />
          </div>
          <div class="field">
            <label for="edit_licence_expiry_date">Licence Expiry Date</label>
            <input type="date" id="edit_licence_expiry_date" name="licence_expiry_date" required />
          </div>
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
  const addModal = document.getElementById('addDriverModal');

  function openAddModal() {
    addModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('name')?.focus());
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
  const editModal = document.getElementById('editDriverModal');
  const editForm  = document.getElementById('editDriverForm');

  function openEditModal(driver) {
    editForm.action = '/panel/drivers/' + driver.id;

    document.getElementById('edit_name').value               = driver.name;
    document.getElementById('edit_email').value              = driver.email ?? '';
    document.getElementById('edit_nic').value                = driver.nic;
    document.getElementById('edit_phone_number').value       = driver.phone_number;
    document.getElementById('edit_licence_number').value     = driver.licence_number;
    document.getElementById('edit_licence_expiry_date').value = driver.licence_expiry_date;

    document.getElementById('editErrors').style.display = 'none';

    editModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('edit_name')?.focus());
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
