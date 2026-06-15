// Admin/supervisor live map. Plots a marker per active bus, moves them in real
// time as BusLocationUpdated events arrive over Echo, and periodically prunes
// markers for buses whose run has ended. The Google Maps SDK is loaded by the
// page with a callback, so google.maps is guaranteed ready before init runs.

let map = null;
let infoWindow = null;
const markers = new Map(); // bus_id -> { marker, data }

function escapeHtml(s) {
    return String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Minutes elapsed since today's departure time ("HH:MM"), formatted compactly.
function elapsedSince(departure) {
    if (!departure) return '—';
    const [h, m] = departure.split(':').map(Number);
    const dep = new Date();
    dep.setHours(h, m, 0, 0);
    let mins = Math.floor((Date.now() - dep.getTime()) / 60000);
    if (mins < 0) mins = 0;
    const hh = Math.floor(mins / 60);
    const mm = mins % 60;
    return hh > 0 ? `${hh}h ${mm}m` : `${mm}m`;
}

function infoContent(d) {
    const row = (label, value) =>
        '<div class="lt-tip__row"><span class="lt-tip__label">' + label + '</span>' +
        '<span class="lt-tip__value">' + escapeHtml(value || '—') + '</span></div>';

    return '<div class="lt-tip">' +
        '<div class="lt-tip__head">' +
            '<span class="lt-tip__dot"></span>' +
            '<span class="lt-tip__reg">' + escapeHtml(d.bus_registration || '—') + '</span>' +
        '</div>' +
        row('Driver', d.driver_name) +
        row('Route', d.route_name) +
        row('Departure', d.departure_time) +
        row('Arrival', d.arrival_time) +
        row('Elapsed', elapsedSince(d.departure_time)) +
    '</div>';
}

function updateCount() {
    const el = document.getElementById('activeBusCount');
    if (el) el.textContent = markers.size;
}

function busMarkerIcon() {
    return {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 9,
        fillColor: '#1a56db',
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2,
    };
}

// Create the marker for a bus, or move the existing one to the new fix.
function upsertMarker(d) {
    if (d.latitude == null || d.longitude == null) return;
    const position = { lat: Number(d.latitude), lng: Number(d.longitude) };
    const entry = markers.get(d.bus_id);

    if (entry) {
        entry.marker.setPosition(position);
        entry.data = d;
        if (infoWindow.getMap() && infoWindow.getAnchor() === entry.marker) {
            infoWindow.setContent(infoContent(d));
        }
        return;
    }

    const marker = new google.maps.Marker({
        position,
        map,
        title: d.bus_registration || '',
        icon: busMarkerIcon(),
    });
    const created = { marker, data: d };
    marker.addListener('click', () => {
        infoWindow.setContent(infoContent(created.data));
        infoWindow.open(map, marker);
    });

    markers.set(d.bus_id, created);
    updateCount();
}

function removeMarker(busId) {
    const entry = markers.get(busId);
    if (!entry) return;
    entry.marker.setMap(null);
    markers.delete(busId);
    updateCount();
}

function fitToMarkers() {
    if (markers.size === 0) return;
    const bounds = new google.maps.LatLngBounds();
    markers.forEach((e) => bounds.extend(e.marker.getPosition()));
    map.fitBounds(bounds);
    if (markers.size === 1) map.setZoom(14);
}

// Poll the active runs and drop any marker whose run is no longer live.
async function pruneEndedRuns() {
    try {
        const res = await fetch('/api/active-run-ids', { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const active = new Set((await res.json()).map(Number));
        for (const busId of [...markers.keys()]) {
            if (!active.has(Number(busId))) removeMarker(busId);
        }
    } catch {
        // Ignore — the next tick will try again.
    }
}

window.initLiveTracking = function (buses) {
    const el = document.getElementById('liveMap');
    if (!el) return;

    map = new google.maps.Map(el, {
        zoom: 8,
        center: { lat: 7.8731, lng: 80.7718 }, // Sri Lanka, matching the route map
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });
    infoWindow = new google.maps.InfoWindow();

    (buses || []).forEach(upsertMarker);
    fitToMarkers();

    // Live updates: each fix moves (or creates) the bus's marker.
    if (window.Echo) {
        window.Echo.channel('live-tracking')
            .listen('.BusLocationUpdated', (e) => upsertMarker(e));
    }

    // Safety net for buses whose run ended without a final event.
    setInterval(pruneEndedRuns, 30000);
};
