@extends('layouts.panel')

@section('title', 'Routes')
@section('page-label', 'Routes')

@section('content')

<div class="page-header">
  <h1 class="page-title">Bus Routes</h1>
  <p class="page-subtitle">Manage bus routes, stops, and view them on Google Maps.</p>
</div>

@if (session('success'))
  <div class="alert alert--success">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    {{ session('success') }}
  </div>
@endif

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Routes <span class="table-count">({{ $routes->total() }})</span></span>
    <button class="btn-primary" onclick="openAddModal()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Route
    </button>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Route Name</th>
        <th>Origin</th>
        <th>Destination</th>
        <th>Stops</th>
        <th>Distance</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($routes as $route)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $routes->firstItem() + $loop->index }}</td>
          <td style="font-weight:500;">{{ $route->name }}</td>
          <td>{{ $route->origin }}</td>
          <td>{{ $route->destination }}</td>
          <td style="color:var(--text-muted);">{{ count($route->stops ?? []) }} stop{{ count($route->stops ?? []) !== 1 ? 's' : '' }}</td>
          <td style="color:var(--text-muted);">{{ number_format($route->distance_km, 1) }} km</td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <button
                class="btn-ghost btn-ghost--sm"
                onclick="openMapModal({{ json_encode([
                  'name'            => $route->name,
                  'origin'          => $route->origin,
                  'origin_lat'      => $route->origin_lat,
                  'origin_lng'      => $route->origin_lng,
                  'destination'     => $route->destination,
                  'destination_lat' => $route->destination_lat,
                  'destination_lng' => $route->destination_lng,
                  'stops'           => $route->stops ?? [],
                  'distance_km'     => $route->distance_km,
                ]) }})"
              >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                </svg>
                View Map
              </button>
              <button
                class="btn-ghost btn-ghost--sm"
                onclick="openEditModal({{ json_encode([
                  'id'              => $route->id,
                  'name'            => $route->name,
                  'origin'          => $route->origin,
                  'origin_lat'      => $route->origin_lat,
                  'origin_lng'      => $route->origin_lng,
                  'destination'     => $route->destination,
                  'destination_lat' => $route->destination_lat,
                  'destination_lng' => $route->destination_lng,
                  'stops'           => $route->stops ?? [],
                  'distance_km'     => $route->distance_km,
                ]) }})"
              >Edit</button>
              <form method="POST" action="{{ route('panel.routes.destroy', $route) }}" onsubmit="return confirm('Delete route \'{{ addslashes($route->name) }}\'?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-ghost btn-ghost--sm" style="color:var(--error);border-color:var(--error);">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px 16px;">
            No routes found. Add one using the button above.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($routes->hasPages())
    <div class="pagination-bar">
      @if ($routes->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $routes->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($routes->getUrlRange(1, $routes->lastPage()) as $page => $url)
        @if ($page == $routes->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($routes->hasMorePages())
        <a href="{{ $routes->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>


{{-- ── Add Route Modal ── --}}
<div class="modal-overlay" id="addRouteModal" onclick="closeAddModalOnOverlay(event)">
  <div class="modal" style="max-width:560px;">

    <div class="modal-header">
      <h2 class="modal-title">Add Bus Route</h2>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" action="{{ route('panel.routes.store') }}" novalidate id="addRouteForm">
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
          <label for="route_name">Route Name</label>
          <input type="text" id="route_name" name="name" value="{{ old('name') }}" placeholder="e.g. Route 42 – City Express" required autocomplete="off" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="origin">Origin (Start)</label>
            <input type="text" id="origin" name="origin" value="{{ old('origin') }}" placeholder="Search for start point…" required autocomplete="off" />
            <input type="hidden" id="origin_lat" name="origin_lat" value="{{ old('origin_lat') }}" />
            <input type="hidden" id="origin_lng" name="origin_lng" value="{{ old('origin_lng') }}" />
          </div>
          <div class="field">
            <label for="destination">Destination (End)</label>
            <input type="text" id="destination" name="destination" value="{{ old('destination') }}" placeholder="Search for end point…" required autocomplete="off" />
            <input type="hidden" id="destination_lat" name="destination_lat" value="{{ old('destination_lat') }}" />
            <input type="hidden" id="destination_lng" name="destination_lng" value="{{ old('destination_lng') }}" />
          </div>
        </div>

        <div style="margin-top:14px;">
          <label style="font-size:13px;font-weight:500;color:var(--text);display:block;margin-bottom:8px;">Stops Along the Way</label>
          <div id="stopsContainer" style="display:flex;flex-direction:column;gap:8px;">
            @if (old('stops'))
              @foreach (old('stops') as $i => $stop)
                <div class="stop-row" data-idx="{{ $i }}">
                  <input type="text" name="stops[{{ $i }}][name]" value="{{ is_array($stop) ? ($stop['name'] ?? '') : $stop }}" placeholder="Search for a stop…" class="stop-input" autocomplete="off" />
                  <input type="hidden" name="stops[{{ $i }}][lat]" value="{{ is_array($stop) ? ($stop['lat'] ?? '') : '' }}" class="stop-lat" />
                  <input type="hidden" name="stops[{{ $i }}][lng]" value="{{ is_array($stop) ? ($stop['lng'] ?? '') : '' }}" class="stop-lng" />
                  <button type="button" class="stop-remove" onclick="removeStop(this)" title="Remove">&times;</button>
                </div>
              @endforeach
            @endif
          </div>
          <button type="button" class="btn-ghost" onclick="addStop()" style="margin-top:8px;font-size:12px;padding:5px 10px;">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Stop
          </button>
        </div>

        {{-- Distance: auto-filled by Directions API, editable as fallback --}}
        <div class="field" style="margin-top:14px;">
          <label for="distance_km">
            Total Distance (km)
            <span id="distCalcBadge" style="display:none;margin-left:6px;font-size:11px;font-weight:400;color:var(--success);background:#f0fdf4;border:1px solid #bbf7d0;padding:1px 6px;border-radius:10px;">
              calculated by Google Maps
            </span>
          </label>
          <div style="position:relative;">
            <input
              type="number"
              id="distance_km"
              name="distance_km"
              value="{{ old('distance_km') }}"
              placeholder="Auto-calculated when origin &amp; destination are set"
              step="0.1"
              min="0.1"
              required
              style="width:100%;padding-right:100px;"
            />
            <button
              type="button"
              id="recalcBtn"
              onclick="calculateDistance()"
              style="position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:11px;padding:3px 8px;background:var(--bg);border:1px solid var(--border);border-radius:3px;color:var(--text-muted);cursor:pointer;display:none;"
            >Recalculate</button>
          </div>
          <span id="distStatus" style="font-size:11px;color:var(--text-muted);margin-top:2px;display:none;"></span>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn-primary">Create Route</button>
      </div>
    </form>
  </div>
</div>


{{-- ── Edit Route Modal ── --}}
<div class="modal-overlay" id="editRouteModal" onclick="closeEditModalOnOverlay(event)">
  <div class="modal" style="max-width:560px;">

    <div class="modal-header">
      <h2 class="modal-title">Edit Bus Route</h2>
      <button class="modal-close" onclick="closeEditModal()" aria-label="Close">&times;</button>
    </div>

    <form method="POST" id="editRouteForm" novalidate>
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
          <label for="edit_name">Route Name</label>
          <input type="text" id="edit_name" name="name" placeholder="e.g. Route 42 – City Express" required autocomplete="off" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
          <div class="field">
            <label for="edit_origin">Origin (Start)</label>
            <input type="text" id="edit_origin" name="origin" placeholder="Search for start point…" required autocomplete="off" />
            <input type="hidden" id="edit_origin_lat" name="origin_lat" />
            <input type="hidden" id="edit_origin_lng" name="origin_lng" />
          </div>
          <div class="field">
            <label for="edit_destination">Destination (End)</label>
            <input type="text" id="edit_destination" name="destination" placeholder="Search for end point…" required autocomplete="off" />
            <input type="hidden" id="edit_destination_lat" name="destination_lat" />
            <input type="hidden" id="edit_destination_lng" name="destination_lng" />
          </div>
        </div>

        <div style="margin-top:14px;">
          <label style="font-size:13px;font-weight:500;color:var(--text);display:block;margin-bottom:8px;">Stops Along the Way</label>
          <div id="editStopsContainer" style="display:flex;flex-direction:column;gap:8px;"></div>
          <button type="button" class="btn-ghost" onclick="addEditStop()" style="margin-top:8px;font-size:12px;padding:5px 10px;">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Stop
          </button>
        </div>

        <div class="field" style="margin-top:14px;">
          <label for="edit_distance_km">
            Total Distance (km)
            <span id="editDistCalcBadge" style="display:none;margin-left:6px;font-size:11px;font-weight:400;color:var(--success);background:#f0fdf4;border:1px solid #bbf7d0;padding:1px 6px;border-radius:10px;">
              calculated by Google Maps
            </span>
          </label>
          <div style="position:relative;">
            <input
              type="number"
              id="edit_distance_km"
              name="distance_km"
              placeholder="Auto-calculated when origin &amp; destination are set"
              step="0.1"
              min="0.1"
              required
              style="width:100%;padding-right:100px;"
            />
            <button
              type="button"
              id="editRecalcBtn"
              onclick="calculateEditDistance()"
              style="position:absolute;right:6px;top:50%;transform:translateY(-50%);font-size:11px;padding:3px 8px;background:var(--bg);border:1px solid var(--border);border-radius:3px;color:var(--text-muted);cursor:pointer;"
            >Recalculate</button>
          </div>
          <span id="editDistStatus" style="font-size:11px;color:var(--text-muted);margin-top:2px;display:none;"></span>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>


{{-- ── Map Modal ── --}}
<div class="modal-overlay" id="mapModal" onclick="closeMapModalOnOverlay(event)">
  <div class="modal map-modal" style="max-width:880px;width:100%;">

    <div class="map-modal-header">
      <div class="map-modal-heading">
        <span class="map-modal-eyebrow">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          Route Map
        </span>
        <h2 class="modal-title" id="mapModalTitle">Route Map</h2>
      </div>
      <button class="modal-close" onclick="closeMapModal()" aria-label="Close">&times;</button>
    </div>

    {{-- Stat strip (filled by JS) --}}
    <div class="map-stats" id="mapStats"></div>

    <div class="map-stage">
      @if (config('services.google_maps.key'))
        <div id="routeMap" class="map-canvas"></div>
      @else
        <div class="map-canvas" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:var(--text-muted);text-align:center;padding:24px;">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4;">
            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
          </svg>
          <div>
            <div style="font-weight:500;color:var(--text);margin-bottom:4px;">Google Maps API key not configured</div>
            <div style="font-size:12px;">Add <code style="background:var(--border);padding:1px 5px;border-radius:3px;">GOOGLE_MAPS_API_KEY</code> to your <code style="background:var(--border);padding:1px 5px;border-radius:3px;">.env</code> file.</div>
          </div>
        </div>
      @endif
    </div>

    <div class="map-journey">
      <div class="map-journey__label">Journey</div>
      <div id="mapRouteStops"></div>
    </div>

  </div>
</div>


<style>
.stop-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.stop-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 3px;
  font-size: 13px;
  font-family: inherit;
  background: var(--surface);
  color: var(--text);
  outline: none;
  transition: border-color 0.15s;
}
.stop-input:focus { border-color: var(--accent); }
.stop-remove {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  background: none;
  border: 1px solid var(--border);
  border-radius: 3px;
  font-size: 16px;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.stop-remove:hover { background:#fef2f2; border-color:var(--error); color:var(--error); }

/* Force Google autocomplete dropdown above modal overlay */
.pac-container { z-index: 9999 !important; }


/* ─── View Map Modal ─────────────────────────────────────── */
.map-modal { overflow: hidden; }

.map-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 22px 16px;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
  flex-shrink: 0;
}
.map-modal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 5px;
}
.map-modal-heading .modal-title { font-size: 17px; line-height: 1.25; }

/* Stat strip */
.map-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 14px 22px;
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.map-stat {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 150px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 9px;
  padding: 9px 12px;
}
.map-stat__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--accent-light);
  color: var(--accent);
}
.map-stat__label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-muted);
}
.map-stat__value {
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.25;
  margin-top: 1px;
  max-width: 170px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Map stage + canvas */
.map-stage { position: relative; flex-shrink: 0; }
.map-canvas {
  width: 100%;
  height: 480px;
  background: var(--bg);
}

/* Journey breadcrumb footer */
.map-journey {
  padding: 14px 22px;
  border-top: 1px solid var(--border);
  background: var(--surface);
  flex-shrink: 0;
}
.map-journey__label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 9px;
}

/* InfoWindow tooltip card */
.map-tip { font-family: inherit; min-width: 120px; padding: 1px 2px 3px; }
.map-tip__role {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b6b6b;
}
.map-tip__dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.map-tip__name { font-size: 13.5px; font-weight: 600; color: #1a1a1a; margin-top: 4px; }

@media (max-width: 640px) {
  .map-canvas { height: 360px; }
  .map-stat { min-width: calc(50% - 5px); }
}
</style>


<script>
  // ─── State ────────────────────────────────────────────────
  let mapsReady = false;
  let stopCounter = {{ old('stops') ? (max(array_keys(old('stops'))) + 1) : 0 }};
  let mapInstance = null;
  let directionsRenderer = null;

  // Called by Google Maps SDK once loaded
  function initGoogleMaps() {
    mapsReady = true;

    // Autocomplete on origin / destination in the add modal
    bindAutocomplete(
      document.getElementById('origin'),
      document.getElementById('origin_lat'),
      document.getElementById('origin_lng'),
      calculateDistance
    );
    bindAutocomplete(
      document.getElementById('destination'),
      document.getElementById('destination_lat'),
      document.getElementById('destination_lng'),
      calculateDistance
    );

    // Attach to any stop inputs restored from old() on validation error.
    // Stops don't affect the (direct) distance, so no recalc on selection.
    document.querySelectorAll('#stopsContainer .stop-row').forEach(row => {
      bindAutocomplete(
        row.querySelector('.stop-input'),
        row.querySelector('.stop-lat'),
        row.querySelector('.stop-lng')
      );
    });
  }

  // Show just the place name the user clicked (e.g. "Kandy Bus Stand"), falling
  // back to the formatted address only when the place has no name. Coordinates
  // are saved separately, so the map stays accurate regardless of the label.
  function placeLabel(place) {
    return (place.name || '').trim() || (place.formatted_address || '').trim();
  }

  // Wire Places Autocomplete to an input, stashing the picked point's exact
  // coordinates into the given hidden lat/lng inputs. `onSelect` (optional) is
  // debounced and called after a successful pick (used to recalc distance).
  function bindAutocomplete(input, latEl, lngEl, onSelect) {
    if (!mapsReady || !input) return;
    const ac = new google.maps.places.Autocomplete(input, {
      fields: ['formatted_address', 'geometry', 'name'],
      componentRestrictions: { country: 'lk' },
    });
    ac.addListener('place_changed', () => {
      const place = ac.getPlace();
      if (place && place.geometry && place.geometry.location) {
        // Show the exact place picked and save its precise coordinates.
        input.value = placeLabel(place);
        if (latEl) latEl.value = place.geometry.location.lat();
        if (lngEl) lngEl.value = place.geometry.location.lng();
      }
      if (onSelect) {
        clearTimeout(window._distDebounce);
        window._distDebounce = setTimeout(onSelect, 400);
      }
    });
    // If the text is edited by hand after a pick, the saved coordinates no
    // longer match what's shown — clear them so we don't store a stale point.
    input.addEventListener('input', () => {
      if (latEl) latEl.value = '';
      if (lngEl) lngEl.value = '';
    });
  }

  // Build a Directions/Geocoder location for a point: use exact coordinates
  // when we have them, otherwise fall back to the text name.
  function pointFor(name, lat, lng) {
    if (lat !== null && lat !== '' && lng !== null && lng !== '' &&
        !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng))) {
      return { lat: parseFloat(lat), lng: parseFloat(lng) };
    }
    return name;
  }


  // ─── Distance Auto-Calculation ────────────────────────────
  function calculateDistance() {
    if (!mapsReady) return;
    const originName = document.getElementById('origin').value.trim();
    const destName   = document.getElementById('destination').value.trim();
    if (!originName || !destName) return;

    const origin = pointFor(originName, document.getElementById('origin_lat').value, document.getElementById('origin_lng').value);
    const dest   = pointFor(destName, document.getElementById('destination_lat').value, document.getElementById('destination_lng').value);

    setDistStatus('Calculating…', 'var(--text-muted)');

    // Measure the direct origin → destination route (no waypoints), so the
    // distance matches the line drawn on the map and Google Maps. Stops lie
    // along this path and are shown as markers, not as detour points.
    const svc = new google.maps.DirectionsService();
    svc.route({
      origin,
      destination: dest,
      waypoints: [],
      provideRouteAlternatives: false,
      travelMode: google.maps.TravelMode.DRIVING,
      region: 'lk',
    }, (result, status) => {
      if (status === 'OK') {
        const totalM = result.routes[0].legs.reduce((s, leg) => s + leg.distance.value, 0);
        const km = (totalM / 1000).toFixed(1);
        document.getElementById('distance_km').value = km;
        document.getElementById('distCalcBadge').style.display = 'inline';
        document.getElementById('recalcBtn').style.display    = 'block';
        setDistStatus('');
      } else {
        setDistStatus('Could not calculate distance (' + status + '). Enter manually.', 'var(--warning)');
        document.getElementById('recalcBtn').style.display = 'block';
      }
    });
  }

  function setDistStatus(msg, color) {
    const el = document.getElementById('distStatus');
    el.textContent = msg;
    el.style.color  = color || '';
    el.style.display = msg ? 'block' : 'none';
  }


  // ─── Add Route Modal ──────────────────────────────────────
  const addModal = document.getElementById('addRouteModal');

  function openAddModal() {
    addModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => document.getElementById('route_name')?.focus());
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


  // ─── Dynamic Stops ────────────────────────────────────────
  function addStop() {
    const container = document.getElementById('stopsContainer');
    const idx = stopCounter++;
    const row = document.createElement('div');
    row.className = 'stop-row';
    row.dataset.idx = idx;
    row.innerHTML = `
      <input type="text" name="stops[${idx}][name]" placeholder="Search for a stop…" class="stop-input" autocomplete="off" />
      <input type="hidden" name="stops[${idx}][lat]" class="stop-lat" />
      <input type="hidden" name="stops[${idx}][lng]" class="stop-lng" />
      <button type="button" class="stop-remove" onclick="removeStop(this)" title="Remove">&times;</button>
    `;
    container.appendChild(row);
    bindAutocomplete(
      row.querySelector('.stop-input'),
      row.querySelector('.stop-lat'),
      row.querySelector('.stop-lng')
    );
    row.querySelector('.stop-input').focus();
  }

  function removeStop(btn) {
    btn.closest('.stop-row').remove();
  }


  // ─── Edit Route Modal ─────────────────────────────────────
  const editModal = document.getElementById('editRouteModal');
  const editForm  = document.getElementById('editRouteForm');
  let editStopCounter = 0;

  function openEditModal(route) {
    // Set form action to PATCH endpoint for this route
    editForm.action = '/panel/routes/' + route.id;

    // Populate fields
    document.getElementById('edit_name').value        = route.name;
    document.getElementById('edit_origin').value      = route.origin;
    document.getElementById('edit_destination').value = route.destination;
    document.getElementById('edit_distance_km').value = route.distance_km;
    document.getElementById('edit_origin_lat').value      = route.origin_lat ?? '';
    document.getElementById('edit_origin_lng').value      = route.origin_lng ?? '';
    document.getElementById('edit_destination_lat').value = route.destination_lat ?? '';
    document.getElementById('edit_destination_lng').value = route.destination_lng ?? '';
    document.getElementById('editDistCalcBadge').style.display = 'none';
    document.getElementById('editDistStatus').style.display    = 'none';

    // Rebuild stops
    const container = document.getElementById('editStopsContainer');
    container.innerHTML = '';
    editStopCounter = 0;
    (route.stops || []).forEach(stop => addEditStop(stop));

    // Clear any previous errors
    document.getElementById('editErrors').style.display = 'none';

    editModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    // Attach autocomplete to edit origin/destination (do this after modal is visible)
    requestAnimationFrame(() => {
      bindAutocomplete(
        document.getElementById('edit_origin'),
        document.getElementById('edit_origin_lat'),
        document.getElementById('edit_origin_lng'),
        calculateEditDistance
      );
      bindAutocomplete(
        document.getElementById('edit_destination'),
        document.getElementById('edit_destination_lat'),
        document.getElementById('edit_destination_lng'),
        calculateEditDistance
      );
      document.getElementById('edit_name').focus();
    });
  }

  function closeEditModal() {
    editModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function closeEditModalOnOverlay(e) {
    if (e.target === editModal) closeEditModal();
  }

  function addEditStop(stop) {
    // `stop` may be an object {name, lat, lng} (new routes) or a plain string
    // (routes created before coordinates were stored).
    const name = stop && typeof stop === 'object' ? (stop.name || '') : (stop || '');
    const lat  = stop && typeof stop === 'object' && stop.lat != null ? stop.lat : '';
    const lng  = stop && typeof stop === 'object' && stop.lng != null ? stop.lng : '';
    const idx  = editStopCounter++;

    const container = document.getElementById('editStopsContainer');
    const row = document.createElement('div');
    row.className = 'stop-row';
    row.innerHTML = `
      <input type="text" name="stops[${idx}][name]" value="${name.replace(/"/g, '&quot;')}" placeholder="Search for a stop…" class="stop-input edit-stop-input" autocomplete="off" />
      <input type="hidden" name="stops[${idx}][lat]" value="${lat}" class="stop-lat" />
      <input type="hidden" name="stops[${idx}][lng]" value="${lng}" class="stop-lng" />
      <button type="button" class="stop-remove" onclick="removeEditStop(this)" title="Remove">&times;</button>
    `;
    container.appendChild(row);
    bindAutocomplete(
      row.querySelector('.stop-input'),
      row.querySelector('.stop-lat'),
      row.querySelector('.stop-lng')
    );

    // Only focus if adding interactively (no pre-filled value)
    if (!name) row.querySelector('.stop-input').focus();
  }

  function removeEditStop(btn) {
    btn.closest('.stop-row').remove();
  }

  function calculateEditDistance() {
    if (!mapsReady) return;
    const originName = document.getElementById('edit_origin').value.trim();
    const destName   = document.getElementById('edit_destination').value.trim();
    if (!originName || !destName) return;

    const origin = pointFor(originName, document.getElementById('edit_origin_lat').value, document.getElementById('edit_origin_lng').value);
    const dest   = pointFor(destName, document.getElementById('edit_destination_lat').value, document.getElementById('edit_destination_lng').value);

    setEditDistStatus('Calculating…', 'var(--text-muted)');

    // Direct origin → destination route (no waypoints) to stay consistent with
    // the map line and Google Maps. Stops are markers along this path.
    new google.maps.DirectionsService().route({
      origin,
      destination: dest,
      waypoints: [],
      provideRouteAlternatives: false,
      travelMode: google.maps.TravelMode.DRIVING,
      region: 'lk',
    }, (result, status) => {
      if (status === 'OK') {
        const km = (result.routes[0].legs.reduce((s, leg) => s + leg.distance.value, 0) / 1000).toFixed(1);
        document.getElementById('edit_distance_km').value = km;
        document.getElementById('editDistCalcBadge').style.display = 'inline';
        setEditDistStatus('');
      } else {
        setEditDistStatus('Could not calculate (' + status + '). Enter manually.', 'var(--warning)');
      }
    });
  }

  function setEditDistStatus(msg, color) {
    const el = document.getElementById('editDistStatus');
    el.textContent  = msg;
    el.style.color  = color || '';
    el.style.display = msg ? 'block' : 'none';
  }

  // ─── Map Modal ────────────────────────────────────────────
  const mapModal = document.getElementById('mapModal');

  function openMapModal(route) {
    document.getElementById('mapModalTitle').textContent = route.name;

    renderMapStats(route);
    renderStopsBreadcrumb(route);

    mapModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    @if (config('services.google_maps.key'))
      requestAnimationFrame(() => renderMap(route));
    @endif
  }

  function closeMapModal() {
    mapModal.classList.remove('is-open');
    document.body.style.overflow = '';
    @if (config('services.google_maps.key'))
      stopRouteGlow();
    @endif
  }

  function closeMapModalOnOverlay(e) {
    if (e.target === mapModal) closeMapModal();
  }

  // A stop may be an object {name, lat, lng} or a plain string (legacy routes).
  function stopName(stop) {
    return stop && typeof stop === 'object' ? (stop.name || '') : (stop || '');
  }

  // Escape user-provided text before injecting into innerHTML.
  function escapeHtml(s) {
    return String(s ?? '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // Fill the stat strip with origin / destination / distance / stops cards.
  function renderMapStats(route) {
    const stopsCount = (route.stops || []).length;
    const dist = parseFloat(route.distance_km).toFixed(1);

    const svg = (inner) => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg>';
    const originIcon = svg('<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/>');
    const destIcon   = svg('<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>');
    const distIcon   = svg('<line x1="3" y1="12" x2="21" y2="12"/><polyline points="7 8 3 12 7 16"/><polyline points="17 8 21 12 17 16"/>');
    const stopIcon   = svg('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/>');

    const card = (icon, label, value) =>
      '<div class="map-stat">' +
        '<div class="map-stat__icon">' + icon + '</div>' +
        '<div style="min-width:0;">' +
          '<div class="map-stat__label">' + label + '</div>' +
          '<div class="map-stat__value" title="' + escapeHtml(value) + '">' + escapeHtml(value) + '</div>' +
        '</div>' +
      '</div>';

    document.getElementById('mapStats').innerHTML =
      card(originIcon, 'Origin', route.origin) +
      card(destIcon, 'Destination', route.destination) +
      card(distIcon, 'Distance', dist + ' km') +
      card(stopIcon, 'Stops', stopsCount + ' stop' + (stopsCount !== 1 ? 's' : ''));
  }

  function renderStopsBreadcrumb(route) {
    const el = document.getElementById('mapRouteStops');
    const stops = (route.stops || []).map(stopName);
    const points = [route.origin, ...stops, route.destination];

    el.innerHTML = '<div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px;">' +
      points.map((p, i) => {
        const isFirst = i === 0, isLast = i === points.length - 1;
        const dot = isFirst
          ? '<span style="width:8px;height:8px;border-radius:50%;background:var(--success);display:inline-block;flex-shrink:0;"></span>'
          : isLast
            ? '<span style="width:8px;height:8px;border-radius:50%;background:var(--error);display:inline-block;flex-shrink:0;"></span>'
            : '<span style="width:6px;height:6px;border-radius:50%;background:var(--border);border:1px solid #aaa;display:inline-block;flex-shrink:0;margin:1px;"></span>';
        const arrow = !isLast
          ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);flex-shrink:0;"><polyline points="9 18 15 12 9 6"/></svg>'
          : '';
        return (
          '<span style="display:inline-flex;align-items:center;gap:6px;background:var(--bg);border:1px solid var(--border);border-radius:20px;padding:4px 11px 4px 9px;">' +
            dot +
            '<span style="color:var(--text);font-weight:500;">' + escapeHtml(p) + '</span>' +
          '</span>' +
          (arrow ? '<span style="display:inline-flex;">' + arrow + '</span>' : '')
        );
      }).join('') +
    '</div>';
  }

  @if (config('services.google_maps.key'))
  let routeMarkers = [];
  let routeInfoWindow = null;
  let glowLayers = [];
  let glowTimer = null;
  let glowPause = null;

  function renderMap(route) {
    const mapEl = document.getElementById('routeMap');

    routeMarkers.forEach(m => m.setMap(null));
    routeMarkers = [];
    if (routeInfoWindow) { routeInfoWindow.close(); }
    stopRouteGlow();
    if (directionsRenderer) { directionsRenderer.setMap(null); directionsRenderer = null; }

    mapInstance = new google.maps.Map(mapEl, {
      zoom: 8,
      center: { lat: 7.8731, lng: 80.7718 },
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true,
    });

    // The map element was just revealed from a display:none modal, so on the
    // first paint it can be created at zero size and render as broken/gray
    // tiles. Force a resize once the modal has laid out.
    google.maps.event.trigger(mapInstance, 'resize');

    // Suppress default A/B markers; we draw our own A / stops / D markers.
    directionsRenderer = new google.maps.DirectionsRenderer({
      suppressMarkers:      true,
      suppressAlternatives: true,
      polylineOptions: { strokeColor: '#1a56db', strokeWeight: 5, strokeOpacity: 0.85 },
    });
    directionsRenderer.setMap(mapInstance);

    // Route origin → destination with NO waypoints so the line follows the
    // clean main road. Stops are drawn as markers snapped onto this line
    // (a bus passes its stops, it doesn't detour off-road to reach each one).
    // Prefer the exact saved coordinates so we plot the picked points, not a
    // re-geocoded guess of the text name.
    new google.maps.DirectionsService().route({
      origin:                   pointFor(route.origin, route.origin_lat, route.origin_lng),
      destination:              pointFor(route.destination, route.destination_lat, route.destination_lng),
      waypoints:                [],
      provideRouteAlternatives: false,
      travelMode:               google.maps.TravelMode.DRIVING,
      region:                   'lk',
    }, (result, status) => {
      if (status !== 'OK') {
        mapEl.innerHTML =
          '<div style="height:100%;display:flex;align-items:center;justify-content:center;' +
          'flex-direction:column;gap:8px;color:var(--text-muted);font-size:13px;padding:24px;text-align:center;">' +
          '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
          '<span>Could not display route: <strong>' + status + '</strong></span>' +
          '<span style="font-size:11px;">Make sure location names were selected from the search dropdown when the route was created.</span>' +
          '</div>';
        return;
      }

      directionsRenderer.setDirections(result);

      // Origin (A) and destination (D) sit exactly at the route endpoints.
      const legs = result.routes[0].legs;
      placeMarker(legs[0].start_location,                  '#16a34a', 11, 'A', route.origin, 'Origin');
      placeMarker(legs[legs.length - 1].end_location,      '#dc2626', 11, 'D', route.destination, 'Destination');

      // Build a dense polyline of the actual driven path (every step's points,
      // not the simplified overview) to snap stops against accurately.
      const path = [];
      legs.forEach(leg => leg.steps.forEach(step => step.path.forEach(p => path.push(p))));

      // Animate a glowing pulse that travels the line origin → destination, on loop.
      startRouteGlow(path);

      // Each stop: use its saved coordinates when present (exact), else geocode
      // the name as a fallback. Either way, drop its numbered marker on the
      // nearest point of the route line — guaranteed on-path, never a detour.
      const stops = route.stops || [];
      const geocoder = new google.maps.Geocoder();
      stops.forEach((stop, i) => {
        const name = stopName(stop).trim();
        if (!name) return;

        const lat = stop && typeof stop === 'object' ? stop.lat : null;
        const lng = stop && typeof stop === 'object' ? stop.lng : null;
        if (lat != null && lng != null && lat !== '' && lng !== '') {
          const snapped = snapToPath(new google.maps.LatLng(parseFloat(lat), parseFloat(lng)), path);
          placeMarker(snapped, '#1a56db', 9, String(i + 1), name, 'Stop ' + (i + 1));
          return;
        }

        geocoder.geocode({ address: name, region: 'lk' }, (res, st) => {
          if (st !== 'OK' || !res[0]) return;
          const snapped = snapToPath(res[0].geometry.location, path);
          placeMarker(snapped, '#1a56db', 9, String(i + 1), name, 'Stop ' + (i + 1));
        });
      });

      // Re-fit to the full route. Doing this after the resize above guarantees
      // a correct zoom/center even though the map was born inside the modal.
      mapInstance.fitBounds(result.routes[0].bounds);
    });
  }

  // Return the point on `path` (array of LatLng) closest to `latLng`.
  function snapToPath(latLng, path) {
    let best = path[0], bestDist = Infinity;
    for (const p of path) {
      const d = google.maps.geometry.spherical.computeDistanceBetween(latLng, p);
      if (d < bestDist) { bestDist = d; best = p; }
    }
    return best;
  }

  // Progressively light the route from origin toward destination in a bright
  // version of the primary colour. Stacked translucent layers (wide+faint →
  // narrow+solid) bloom into a real glow around a crisp core. The filled trail
  // behind the head stays lit; once the whole line is lit it pauses ~1s,
  // clears, and fills again, on loop.
  function startRouteGlow(path) {
    stopRouteGlow();

    const n = path.length;
    if (n < 2) return;

    const step = Math.max(1, Math.round(n * 0.012));  // fill speed per tick

    // Outer → inner: a broad faint halo blooms outward, a solid bright core sits
    // on top. The core is the route's width; the halo gives the glowy bleed.
    glowLayers = [
      { weight: 5, opacity: 1.0, color: '#5f82d6' },
    ].map(l => new google.maps.Polyline({
      path:          [],
      strokeColor:   l.color,
      strokeOpacity: l.opacity,
      strokeWeight:  l.weight,
      zIndex:        6,
      map:           mapInstance,
    }));

    let head = 0;
    let paused = false;
    glowTimer = setInterval(() => {
      if (paused) return;
      head += step;
      // Fill from the origin up to the head — the trail behind stays lit.
      const lit = path.slice(0, Math.min(head, n));
      glowLayers.forEach(l => l.setPath(lit));
      if (head >= n) {
        // Whole line is lit — hold ~1s, then clear and fill again.
        paused = true;
        glowPause = setTimeout(() => {
          glowLayers.forEach(l => l.setPath([]));
          head = 0;
          paused = false;
        }, 1000);
      }
    }, 18);
  }

  function stopRouteGlow() {
    if (glowTimer) { clearInterval(glowTimer); glowTimer = null; }
    if (glowPause) { clearTimeout(glowPause); glowPause = null; }
    glowLayers.forEach(l => l.setMap(null));
    glowLayers = [];
  }

  function placeMarker(position, color, scale, label, title, role) {
    const marker = new google.maps.Marker({
      position,
      map:   mapInstance,
      title,
      label: { text: label, color: '#fff', fontSize: '10px', fontWeight: 'bold' },
      icon: {
        path:         google.maps.SymbolPath.CIRCLE,
        scale,
        fillColor:    color,
        fillOpacity:  1,
        strokeColor:  '#fff',
        strokeWeight: 2,
      },
      zIndex: (label === 'A' || label === 'D') ? 10 : 5,
    });

    // Click a point to reveal a styled tooltip with its role + name.
    marker.addListener('click', () => {
      if (!routeInfoWindow) {
        routeInfoWindow = new google.maps.InfoWindow();
      }
      routeInfoWindow.setContent(
        '<div class="map-tip">' +
          '<div class="map-tip__role">' +
            '<span class="map-tip__dot" style="background:' + color + ';"></span>' +
            escapeHtml(role || '') +
          '</div>' +
          '<div class="map-tip__name">' + escapeHtml(title || '') + '</div>' +
        '</div>'
      );
      routeInfoWindow.open(mapInstance, marker);
    });

    routeMarkers.push(marker);
  }
  @endif
</script>

@if (config('services.google_maps.key'))
  <script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places,geometry&callback=initGoogleMaps"
    async defer
  ></script>
@endif

@endsection
