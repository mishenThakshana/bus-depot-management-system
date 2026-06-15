@extends('layouts.panel')

@section('title', 'Live Tracking')
@section('page-label', 'Live Tracking')

@section('content')

<div class="page-header">
  <h1 class="page-title">Live Tracking</h1>
  <p class="page-subtitle">Follow buses on active runs in real time as their drivers share location.</p>
</div>

<div class="live-tracking">
  <div class="live-tracking__bar">
    <span class="live-tracking__count">
      <span class="live-tracking__pulse"></span>
      <span><span class="live-tracking__count-num" id="activeBusCount">{{ $buses->count() }}</span> buses sharing location</span>
    </span>
  </div>

  @if ($apiKey)
    <div class="live-tracking__map" id="liveMap"></div>
  @else
    <div class="live-tracking__map">
      <div class="live-tracking__empty">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.4;">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        <div>
          <div style="font-weight:500;color:var(--text);margin-bottom:4px;">Google Maps API key not configured</div>
          <div style="font-size:12px;">Add <code style="background:var(--border);padding:1px 5px;border-radius:3px;">GOOGLE_MAPS_API_KEY</code> to your <code style="background:var(--border);padding:1px 5px;border-radius:3px;">.env</code> file.</div>
        </div>
      </div>
    </div>
  @endif
</div>


@if ($apiKey)
<script>
  // Buses with an active run and a recent fix, ready for the first paint.
  window.__liveBuses = {{ Illuminate\Support\Js::from($buses) }};

  // Google Maps invokes this once its SDK is ready.
  function initLiveTrackingMap() {
    window.initLiveTracking(window.__liveBuses);
  }
</script>
<script
  src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&callback=initLiveTrackingMap"
  async defer
></script>
@endif

@endsection
