@extends('layouts.panel')

@section('title', 'Schedules')
@section('page-label', 'Schedules')

@section('content')

<div class="page-header">
  <h1 class="page-title">Schedules</h1>
  <p class="page-subtitle">Plan and review trip schedules.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Schedules</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Schedule ID</th>
        <th>Route</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Departure</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">No schedules found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
