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

// A flat, top-down bus sitting on the road (ride-app style), rotated toward its
// heading so it points the way it's travelling. Drawn as an inline SVG and
// anchored at its centre, so the vehicle straddles the bus's exact position.
function busMarkerIcon(heading) {
    const h = Number.isFinite(heading) ? heading : 0;
    const svg =
        '<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44">' +
        '<g transform="rotate(' + h + ' 22 22)">' +
            '<rect x="13.5" y="7.5" width="18" height="31" rx="6.5" fill="#000000" opacity="0.18"/>' +
            '<rect x="13" y="6" width="18" height="31" rx="6.5" fill="#1a56db" stroke="#ffffff" stroke-width="2"/>' +
            '<path d="M15.5 12 Q22 8.5 28.5 12 L28.5 15 Q22 13 15.5 15 Z" fill="#dbe7fb"/>' +
            '<rect x="16" y="30.5" width="12" height="3.2" rx="1.4" fill="#dbe7fb"/>' +
            '<rect x="20.6" y="17" width="2.8" height="11" rx="1.4" fill="#ffffff" opacity="0.55"/>' +
        '</g>' +
        '</svg>';

    return {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
        scaledSize: new google.maps.Size(44, 44),
        anchor: new google.maps.Point(22, 22),
    };
}

// Compass bearing (degrees, 0 = north) from one lat/lng to another.
function bearing(a, b) {
    const toRad = (d) => (d * Math.PI) / 180;
    const la1 = toRad(a.lat);
    const la2 = toRad(b.lat);
    const dLng = toRad(b.lng - a.lng);
    const y = Math.sin(dLng) * Math.cos(la2);
    const x = Math.cos(la1) * Math.sin(la2) - Math.sin(la1) * Math.cos(la2) * Math.cos(dLng);
    return (Math.atan2(y, x) * 180) / Math.PI;
}

// Create the marker for a bus, or move the existing one to the new fix.
function upsertMarker(d) {
    if (d.latitude == null || d.longitude == null) return;
    const position = { lat: Number(d.latitude), lng: Number(d.longitude) };
    const entry = markers.get(d.bus_id);

    if (entry) {
        // Rotate toward the direction of travel; keep the last heading if the
        // bus hasn't actually moved.
        const from = entry.marker.getPosition();
        const prev = { lat: from.lat(), lng: from.lng() };
        if (prev.lat !== position.lat || prev.lng !== position.lng) {
            entry.heading = bearing(prev, position);
        }
        entry.marker.setPosition(position);
        entry.marker.setIcon(busMarkerIcon(entry.heading));
        entry.data = d;
        if (infoWindow.getMap() && infoWindow.getAnchor() === entry.marker) {
            infoWindow.setContent(infoContent(d));
        }
        return;
    }

    const heading = Number.isFinite(d.heading) ? d.heading : 0;
    const marker = new google.maps.Marker({
        position,
        map,
        title: d.bus_registration || '',
        icon: busMarkerIcon(heading),
    });
    const created = { marker, data: d, heading };
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
