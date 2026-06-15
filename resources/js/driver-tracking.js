// Driver-side GPS tracking. While the signed-in driver has a run in progress,
// the browser streams its position to the server. It stops on its own when the
// server reports the run has ended (403), and shows a quiet status indicator —
// never an alert — so it stays out of the driver's way.

let watchId = null;

// Reflect tracking state on the passive indicator (styled via [data-state]).
function setStatus(state, text) {
    const el = document.getElementById('tracking-status');
    if (!el) return;
    el.dataset.state = state;
    const label = el.querySelector('.tracking-status__label');
    if (label) label.textContent = text;
}

function stopTracking() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
}

async function sendFix(position) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const { latitude, longitude, speed, accuracy } = position.coords;

    try {
        const res = await fetch('/api/location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                latitude,
                longitude,
                speed: speed == null ? null : Math.max(0, speed),
                accuracy: accuracy == null ? null : accuracy,
            }),
        });

        if (res.status === 403) {
            // The run is over or was cancelled — there is nothing left to share.
            setStatus('ended', 'Run ended — location sharing stopped');
            stopTracking();
            return;
        }

        if (res.ok) {
            setStatus('live', 'Live — sharing your location');
        }
    } catch {
        // A dropped request is transient; the next fix will retry on its own.
        setStatus('paused', 'Reconnecting…');
    }
}

// Begin watching position. Called from the driver schedule page only when the
// driver has a run live right now.
window.initDriverTracking = function () {
    if (!('geolocation' in navigator)) {
        setStatus('off', 'Location is not available on this device');
        return;
    }

    setStatus('connecting', 'Starting location sharing…');

    watchId = navigator.geolocation.watchPosition(
        sendFix,
        (err) => {
            if (err.code === err.PERMISSION_DENIED) {
                setStatus('denied', 'Location permission denied');
                stopTracking();
            } else {
                setStatus('paused', 'Waiting for a location signal…');
            }
        },
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 },
    );
};
