@extends('layouts.panel')

@section('title', 'Fuel & Maintenance')
@section('page-label', 'Fuel & Maintenance')

@section('content')

<div class="page-header">
  <h1 class="page-title">Fuel &amp; Maintenance</h1>
  <p class="page-subtitle">Track fuel consumption and bus maintenance records across the fleet.</p>
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

{{-- Tab Bar --}}
<div class="fm-tabs">
  <a href="{{ route('panel.fuel', ['tab' => 'fuel']) }}"
     class="fm-tab {{ $tab === 'fuel' ? 'is-active' : '' }}">
    Fuel Records
    <span class="fm-tab-count">{{ $fuelLogs->total() }}</span>
  </a>
  <a href="{{ route('panel.fuel', ['tab' => 'maintenance']) }}"
     class="fm-tab {{ $tab === 'maintenance' ? 'is-active' : '' }}">
    Maintenance
    <span class="fm-tab-count">{{ $maintenanceRecords->total() }}</span>
  </a>
</div>


{{-- ══════════════════════════════════════════════════════════
     FUEL TAB
════════════════════════════════════════════════════════════ --}}
@if ($tab === 'fuel')

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Fuel Records <span class="table-count">({{ $fuelLogs->total() }})</span></span>
    <button class="btn-primary" onclick="openAddFuelModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Log Fuel
    </button>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Date</th>
        <th>Litres</th>
        <th>Cost/L</th>
        <th>Total Cost</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($fuelLogs as $log)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $fuelLogs->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $log->bus->registration_number }}</td>
          <td>{{ $log->driver?->name ?? '—' }}</td>
          <td style="color:var(--text-muted);">{{ $log->fuel_date->format('d M Y') }}</td>
          <td>{{ number_format($log->litres, 1) }} L</td>
          <td style="color:var(--text-muted);">LKR {{ number_format($log->cost_per_litre, 2) }}</td>
          <td style="font-weight:600;">LKR {{ number_format($log->total_cost, 2) }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <button class="btn-ghost btn-ghost--sm" onclick="openEditFuelModal({{ json_encode([
                'id'             => $log->id,
                'bus_id'         => $log->bus_id,
                'driver_id'      => $log->driver_id,
                'fuel_date'      => $log->fuel_date->format('Y-m-d'),
                'litres'         => $log->litres,
                'cost_per_litre' => $log->cost_per_litre,
                'notes'          => $log->notes,
              ]) }})">Edit</button>
              <form method="POST" action="{{ route('panel.fuel.destroy', $log) }}" onsubmit="return confirm('Remove this fuel log?')">
                @csrf @method('DELETE')
                <input type="hidden" name="tab" value="fuel">
                <button type="submit" class="btn-ghost btn-ghost--sm" style="color:var(--error);border-color:var(--error);">Remove</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No fuel records yet. Click "Log Fuel" to add one.
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

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Maintenance Records <span class="table-count">({{ $maintenanceRecords->total() }})</span></span>
    <button class="btn-primary" onclick="openAddMaintModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Record
    </button>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Bus</th>
        <th>Type</th>
        <th>Description</th>
        <th>Date</th>
        <th>Notes</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($maintenanceRecords as $record)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $maintenanceRecords->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $record->bus->registration_number }}</td>
          <td>{{ $record->maintenance_type }}</td>
          <td style="color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $record->description }}</td>
          <td style="color:var(--text-muted);">{{ $record->serviced_date->format('d M Y') }}</td>
          <td style="color:var(--text-muted);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $record->notes ?? '—' }}</td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <button class="btn-ghost btn-ghost--sm" onclick="openEditMaintModal({{ json_encode([
                'id'               => $record->id,
                'bus_id'           => $record->bus_id,
                'maintenance_type' => $record->maintenance_type,
                'description'      => $record->description,
                'serviced_date' => $record->serviced_date->format('Y-m-d'),
                'notes'         => $record->notes,
              ]) }})">Edit</button>
              <form method="POST" action="{{ route('panel.maintenance.destroy', $record) }}" onsubmit="return confirm('Remove this maintenance record?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-ghost btn-ghost--sm" style="color:var(--error);border-color:var(--error);">Remove</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No maintenance records yet. Click "Add Record" to create one.
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
     ADD FUEL MODAL
════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="addFuelModal" onclick="if(event.target===this)closeAddFuelModal()">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h2 class="modal-title">Log Fuel</h2>
      <button class="modal-close" onclick="closeAddFuelModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('panel.fuel.store') }}" novalidate>
      @csrf
      <div class="modal-body">
        @if ($errors->any() && $tab === 'fuel' && !request()->has('edit'))
          <div class="alert alert--error">
            <div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
          </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="field">
            <label>Bus</label>
            <select name="bus_id" required>
              <option value="" disabled selected>Select bus…</option>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}" {{ old('bus_id') == $bus->id ? 'selected' : '' }}>{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Driver <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
            <select name="driver_id">
              <option value="">None</option>
              @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Date</label>
          <input type="date" name="fuel_date" value="{{ old('fuel_date', date('Y-m-d')) }}" required />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label>Litres</label>
            <input type="number" name="litres" value="{{ old('litres') }}" placeholder="e.g. 80.50" step="0.01" min="0.01" required />
          </div>
          <div class="field">
            <label>Cost per Litre (LKR)</label>
            <input type="number" name="cost_per_litre" value="{{ old('cost_per_litre') }}" placeholder="e.g. 340.00" step="0.01" min="0.01" required />
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="notes" rows="2" placeholder="Any additional notes…" style="resize:vertical;">{{ old('notes') }}</textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddFuelModal()">Cancel</button>
        <button type="submit" class="btn-primary">Log Fuel</button>
      </div>
    </form>
  </div>
</div>


{{-- ══════════════════════════════════════════════════════════
     EDIT FUEL MODAL
════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="editFuelModal" onclick="if(event.target===this)closeEditFuelModal()">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <h2 class="modal-title">Edit Fuel Log</h2>
      <button class="modal-close" onclick="closeEditFuelModal()">&times;</button>
    </div>
    <form method="POST" id="editFuelForm" novalidate>
      @csrf @method('PATCH')
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="field">
            <label>Bus</label>
            <select id="ef_bus_id" name="bus_id" required>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}">{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Driver <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
            <select id="ef_driver_id" name="driver_id">
              <option value="">None</option>
              @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="field" style="margin-top:14px;">
          <label>Date</label>
          <input type="date" id="ef_fuel_date" name="fuel_date" required />
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label>Litres</label>
            <input type="number" id="ef_litres" name="litres" step="0.01" min="0.01" required />
          </div>
          <div class="field">
            <label>Cost per Litre (LKR)</label>
            <input type="number" id="ef_cost_per_litre" name="cost_per_litre" step="0.01" min="0.01" required />
          </div>
        </div>
        <div class="field" style="margin-top:14px;">
          <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea id="ef_notes" name="notes" rows="2" style="resize:vertical;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeEditFuelModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>


{{-- ══════════════════════════════════════════════════════════
     ADD MAINTENANCE MODAL
════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="addMaintModal" onclick="if(event.target===this)closeAddMaintModal()">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h2 class="modal-title">Add Maintenance Record</h2>
      <button class="modal-close" onclick="closeAddMaintModal()">&times;</button>
    </div>
    <form method="POST" action="{{ route('panel.maintenance.store') }}" novalidate>
      @csrf
      <div class="modal-body">
        @if ($errors->any() && $tab === 'maintenance' && !request()->has('edit'))
          <div class="alert alert--error">
            <div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
          </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="field">
            <label>Bus</label>
            <select name="bus_id" required>
              <option value="" disabled selected>Select bus…</option>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}" {{ old('bus_id') == $bus->id ? 'selected' : '' }}>{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Maintenance Type</label>
            <select name="maintenance_type" required>
              <option value="" disabled selected>Select type…</option>
              @foreach (\App\Models\MaintenanceRecord::$types as $type)
                <option value="{{ $type }}" {{ old('maintenance_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Description</label>
          <textarea name="description" rows="2" placeholder="Describe the work performed…" style="resize:vertical;" required>{{ old('description') }}</textarea>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Service Date</label>
          <input type="date" name="serviced_date" value="{{ old('serviced_date', date('Y-m-d')) }}" required />
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea name="notes" rows="2" placeholder="Any additional notes…" style="resize:vertical;">{{ old('notes') }}</textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddMaintModal()">Cancel</button>
        <button type="submit" class="btn-primary">Add Record</button>
      </div>
    </form>
  </div>
</div>


{{-- ══════════════════════════════════════════════════════════
     EDIT MAINTENANCE MODAL
════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="editMaintModal" onclick="if(event.target===this)closeEditMaintModal()">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h2 class="modal-title">Edit Maintenance Record</h2>
      <button class="modal-close" onclick="closeEditMaintModal()">&times;</button>
    </div>
    <form method="POST" id="editMaintForm" novalidate>
      @csrf @method('PATCH')
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="field">
            <label>Bus</label>
            <select id="em_bus_id" name="bus_id" required>
              @foreach ($buses as $bus)
                <option value="{{ $bus->id }}">{{ $bus->registration_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label>Maintenance Type</label>
            <select id="em_maintenance_type" name="maintenance_type" required>
              @foreach (\App\Models\MaintenanceRecord::$types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Description</label>
          <textarea id="em_description" name="description" rows="2" style="resize:vertical;" required></textarea>
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Service Date</label>
          <input type="date" id="em_serviced_date" name="serviced_date" required />
        </div>

        <div class="field" style="margin-top:14px;">
          <label>Notes <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
          <textarea id="em_notes" name="notes" rows="2" style="resize:vertical;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeEditMaintModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>


<style>
.fm-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 0;
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
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600; letter-spacing: 0.03em;
  padding: 2px 8px; border-radius: 20px; border: 1px solid transparent;
}
.status-badge--active    { color: var(--success); background: #f0fdf4; border-color: #bbf7d0; }
.status-badge--scheduled { color: #d97706; background: #fffbeb; border-color: #fde68a; }
</style>


<script>
// ── Fuel Add Modal ──────────────────────────────────────────
const addFuelModal = document.getElementById('addFuelModal');
function openAddFuelModal() { addFuelModal.classList.add('is-open'); document.body.style.overflow='hidden'; }
function closeAddFuelModal() { addFuelModal.classList.remove('is-open'); document.body.style.overflow=''; }

// ── Fuel Edit Modal ─────────────────────────────────────────
const editFuelModal = document.getElementById('editFuelModal');
const editFuelForm  = document.getElementById('editFuelForm');
function openEditFuelModal(log) {
  editFuelForm.action = '/panel/fuel/' + log.id;
  document.getElementById('ef_bus_id').value           = log.bus_id;
  document.getElementById('ef_driver_id').value        = log.driver_id ?? '';
  document.getElementById('ef_fuel_date').value        = log.fuel_date;
  document.getElementById('ef_litres').value           = log.litres;
  document.getElementById('ef_cost_per_litre').value   = log.cost_per_litre;
  document.getElementById('ef_notes').value            = log.notes ?? '';
  editFuelModal.classList.add('is-open'); document.body.style.overflow='hidden';
}
function closeEditFuelModal() { editFuelModal.classList.remove('is-open'); document.body.style.overflow=''; }

// ── Maintenance Add Modal ───────────────────────────────────
const addMaintModal = document.getElementById('addMaintModal');
function openAddMaintModal() { addMaintModal.classList.add('is-open'); document.body.style.overflow='hidden'; }
function closeAddMaintModal() { addMaintModal.classList.remove('is-open'); document.body.style.overflow=''; }

// ── Maintenance Edit Modal ──────────────────────────────────
const editMaintModal = document.getElementById('editMaintModal');
const editMaintForm  = document.getElementById('editMaintForm');
function openEditMaintModal(rec) {
  editMaintForm.action = '/panel/maintenance/' + rec.id;
  document.getElementById('em_bus_id').value           = rec.bus_id;
  document.getElementById('em_maintenance_type').value = rec.maintenance_type;
  document.getElementById('em_description').value      = rec.description;
  document.getElementById('em_serviced_date').value = rec.serviced_date;
  document.getElementById('em_notes').value         = rec.notes ?? '';
  editMaintModal.classList.add('is-open'); document.body.style.overflow='hidden';
}
function closeEditMaintModal() { editMaintModal.classList.remove('is-open'); document.body.style.overflow=''; }

// Re-open modals on validation error
@if ($errors->any() && $tab === 'fuel')
  document.addEventListener('DOMContentLoaded', openAddFuelModal);
@elseif ($errors->any() && $tab === 'maintenance')
  document.addEventListener('DOMContentLoaded', openAddMaintModal);
@endif
</script>

@endsection
