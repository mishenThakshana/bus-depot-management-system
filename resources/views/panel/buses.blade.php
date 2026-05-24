@extends('layouts.panel')

@section('title', 'Buses')
@section('page-label', 'Buses')

@section('content')

<div class="page-header">
  <h1 class="page-title">Buses</h1>
  <p class="page-subtitle">Manage the depot fleet.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Buses</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Bus ID</th>
        <th>Plate Number</th>
        <th>Model</th>
        <th>Capacity</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No buses found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
