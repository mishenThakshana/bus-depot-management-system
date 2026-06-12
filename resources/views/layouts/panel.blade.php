<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Panel') — Bus Depot MS</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="panel-root">

  {{-- Top Header --}}
  <header class="panel-header">
    <div class="panel-header-left">
      {{-- Logo --}}
      <a href="{{ route(auth()->user()->homeRoute()) }}" class="panel-logo">
        <div class="panel-logo-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="3" width="15" height="13" rx="2"/>
            <path d="M16 8h4l3 3v5h-7V8z"/>
            <circle cx="5.5" cy="18.5" r="2.5"/>
            <circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
        </div>
        <span class="panel-logo-name">Bus Depot MS</span>
      </a>

      <div class="panel-header-sep"></div>

      {{-- Current page label --}}
      <span style="font-size:13px; color:var(--text-muted);">@yield('page-label', 'Dashboard')</span>
    </div>

    <div class="panel-header-right">

      {{-- User --}}
      <div class="panel-user">
        <span class="panel-username">{{ auth()->user()->name }}</span>
      </div>

      {{-- Sign out --}}
      <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="panel-signout">Sign out</button>
      </form>

    </div>
  </header>

  {{-- Body: Sidebar + Content --}}
  <div class="panel-body">

    {{-- Sidebar --}}
    <aside class="panel-sidebar">
      <div class="sidebar-role-bar">
        <div class="sidebar-role-label">Signed in as</div>
        <div class="sidebar-role-name">{{ auth()->user()->getRoleLabel() }}</div>
      </div>

      <nav class="sidebar-nav">

        {{-- ── ADMIN NAV ── --}}
        <div class="sidebar-nav-group {{ auth()->user()->isAdmin() ? 'is-active' : '' }}" id="nav-admin">

          <a href="{{ route('panel.dashboard') }}" class="nav-item {{ request()->routeIs('panel.dashboard') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
              </svg>
            </span>
            <span class="nav-item-text">Dashboard</span>
          </a>

          <a href="{{ route('panel.users') }}" class="nav-item {{ request()->routeIs('panel.users') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </span>
            <span class="nav-item-text">User Management</span>
          </a>

          <a href="{{ route('panel.routes') }}" class="nav-item {{ request()->routeIs('panel.routes') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 11l19-9-9 19-2-8-8-2z"/>
              </svg>
            </span>
            <span class="nav-item-text">Routes</span>
          </a>

          <a href="{{ route('panel.buses') }}" class="nav-item {{ request()->routeIs('panel.buses') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
            </span>
            <span class="nav-item-text">Buses</span>
          </a>

          <a href="{{ route('panel.drivers') }}" class="nav-item {{ request()->routeIs('panel.drivers') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="5"/><path d="M3 21v-1a9 9 0 0 1 18 0v1"/>
              </svg>
            </span>
            <span class="nav-item-text">Drivers</span>
          </a>

          <a href="{{ route('panel.schedules') }}" class="nav-item {{ request()->routeIs('panel.schedules') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </span>
            <span class="nav-item-text">Schedules</span>
          </a>

          <a href="{{ route('panel.fuel') }}" class="nav-item {{ request()->routeIs('panel.fuel') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
              </svg>
            </span>
            <span class="nav-item-text">Fuel &amp; Maintenance</span>
          </a>

          <a href="{{ route('panel.reports') }}" class="nav-item {{ request()->routeIs('panel.reports') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
              </svg>
            </span>
            <span class="nav-item-text">Reports</span>
          </a>

          <a href="{{ route('panel.audit-log') }}" class="nav-item {{ request()->routeIs('panel.audit-log') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
              </svg>
            </span>
            <span class="nav-item-text">Audit Log</span>
          </a>

        </div>{{-- /nav-admin --}}


        {{-- ── SUPERVISOR NAV ── --}}
        <div class="sidebar-nav-group {{ auth()->user()->isSupervisor() ? 'is-active' : '' }}" id="nav-supervisor">

          <a href="{{ route('panel.dashboard') }}" class="nav-item {{ request()->routeIs('panel.dashboard') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
              </svg>
            </span>
            <span class="nav-item-text">Dashboard</span>
          </a>

          <a href="{{ route('panel.routes') }}" class="nav-item {{ request()->routeIs('panel.routes') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 11l19-9-9 19-2-8-8-2z"/>
              </svg>
            </span>
            <span class="nav-item-text">Routes</span>
          </a>

          <a href="{{ route('panel.buses') }}" class="nav-item {{ request()->routeIs('panel.buses') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
            </span>
            <span class="nav-item-text">Buses</span>
          </a>

          <a href="{{ route('panel.drivers') }}" class="nav-item {{ request()->routeIs('panel.drivers') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="5"/><path d="M3 21v-1a9 9 0 0 1 18 0v1"/>
              </svg>
            </span>
            <span class="nav-item-text">Drivers</span>
          </a>

          <a href="{{ route('panel.schedules') }}" class="nav-item {{ request()->routeIs('panel.schedules') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </span>
            <span class="nav-item-text">Schedules</span>
          </a>

          <a href="{{ route('panel.fuel') }}" class="nav-item {{ request()->routeIs('panel.fuel') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
              </svg>
            </span>
            <span class="nav-item-text">Fuel &amp; Maintenance</span>
          </a>

          <a href="{{ route('panel.reports') }}" class="nav-item {{ request()->routeIs('panel.reports') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
              </svg>
            </span>
            <span class="nav-item-text">Reports</span>
          </a>

        </div>{{-- /nav-supervisor --}}


        {{-- ── DRIVER NAV ── --}}
        <div class="sidebar-nav-group {{ auth()->user()->isDriver() ? 'is-active' : '' }}" id="nav-driver">

          <a href="{{ route('panel.my-schedule') }}" class="nav-item {{ request()->routeIs('panel.my-schedule') ? 'is-active' : '' }}">
            <span class="nav-item-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </span>
            <span class="nav-item-text">My Schedule</span>
          </a>

        </div>{{-- /nav-driver --}}


      </nav>
    </aside>

    {{-- Main Content --}}
    <main class="panel-content">
      @yield('content')
    </main>

  </div>{{-- /panel-body --}}
</div>{{-- /panel-root --}}


<script>
  document.addEventListener('submit', function (e) {
    const btn = e.target.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = '0.55';
      btn.style.cursor = 'not-allowed';
    }
  });
</script>
</body>
</html>
