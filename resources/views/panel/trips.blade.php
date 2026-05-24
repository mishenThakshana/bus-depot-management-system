@extends('layouts.panel')

@section('title', 'Trips')
@section('page-label', 'Trips')

@section('content')

<div class="page-header">
  <h1 class="page-title">Trips</h1>
  <p class="page-subtitle">Track all active and past trips.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Trips</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Trip ID</th>
        <th>Route</th>
        <th>Bus</th>
        <th>Driver</th>
        <th>Departed</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">No trips found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
