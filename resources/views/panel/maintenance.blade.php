@extends('layouts.panel')

@section('title', 'Maintenance Logs')
@section('page-label', 'Maintenance Logs')

@section('content')

<div class="page-header">
  <h1 class="page-title">Maintenance Logs</h1>
  <p class="page-subtitle">Track bus servicing and repair history.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Maintenance Records</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Record ID</th>
        <th>Bus</th>
        <th>Type</th>
        <th>Technician</th>
        <th>Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">No maintenance records found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
