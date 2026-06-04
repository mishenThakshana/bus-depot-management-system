@extends('layouts.panel')

@section('title', 'Fuel Logs')
@section('page-label', 'Fuel Logs')

@section('content')

<div class="page-header">
  <h1 class="page-title">Fuel Logs</h1>
  <p class="page-subtitle">View all fuel records for the fleet.</p>
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
    <span class="table-title">Fuel Records <span class="table-count">({{ $fuelLogs->total() }})</span></span>
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
        <th>Notes</th>
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
          <td style="color:var(--text-muted);font-size:12px;">{{ $log->notes ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px 16px;">No fuel records found.</td>
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

@endsection
