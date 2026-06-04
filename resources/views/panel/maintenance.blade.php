@extends('layouts.panel')

@section('title', 'Maintenance Logs')
@section('page-label', 'Maintenance Logs')

@section('content')

<div class="page-header">
  <h1 class="page-title">Maintenance Logs</h1>
  <p class="page-subtitle">View bus servicing and repair history across the fleet.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Maintenance Records <span class="table-count">({{ $records->total() }})</span></span>
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
      </tr>
    </thead>
    <tbody>
      @forelse ($records as $record)
        <tr>
          <td style="color:var(--text-muted);font-size:12px;">{{ $records->firstItem() + $loop->index }}</td>
          <td style="font-weight:600;">{{ $record->bus->registration_number }}</td>
          <td>{{ $record->maintenance_type }}</td>
          <td style="color:var(--text-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $record->description }}</td>
          <td style="color:var(--text-muted);">{{ $record->serviced_date->format('d M Y') }}</td>
          <td style="color:var(--text-muted);font-size:12px;">{{ $record->notes ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px 16px;">No maintenance records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @if ($records->hasPages())
    <div class="pagination-bar">
      @if ($records->onFirstPage())
        <span class="page-btn page-btn--disabled">&lsaquo;</span>
      @else
        <a href="{{ $records->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
      @endif
      @foreach ($records->getUrlRange(1, $records->lastPage()) as $page => $url)
        @if ($page == $records->currentPage())
          <span class="page-btn page-btn--active">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach
      @if ($records->hasMorePages())
        <a href="{{ $records->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
      @else
        <span class="page-btn page-btn--disabled">&rsaquo;</span>
      @endif
    </div>
  @endif
</div>

@endsection
