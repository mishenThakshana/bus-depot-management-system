@extends('layouts.panel')

@section('title', 'Fuel Logs')
@section('page-label', 'Fuel Logs')

@section('content')

<div class="page-header">
  <h1 class="page-title">Fuel Logs</h1>
  <p class="page-subtitle">Record and review fuel fill-ups.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Fuel Log Entries</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Log ID</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Litres</th>
        <th>Cost</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">No fuel logs found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
