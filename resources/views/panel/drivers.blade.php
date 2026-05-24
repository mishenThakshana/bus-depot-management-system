@extends('layouts.panel')

@section('title', 'Drivers')
@section('page-label', 'Drivers')

@section('content')

<div class="page-header">
  <h1 class="page-title">Drivers</h1>
  <p class="page-subtitle">Manage driver records and assignments.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Drivers</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Driver ID</th>
        <th>Name</th>
        <th>Licence No.</th>
        <th>Assigned Bus</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No drivers found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
