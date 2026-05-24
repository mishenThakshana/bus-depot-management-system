@extends('layouts.panel')

@section('title', 'Routes')
@section('page-label', 'Routes')

@section('content')

<div class="page-header">
  <h1 class="page-title">Routes</h1>
  <p class="page-subtitle">View and manage bus routes.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Routes</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Route ID</th>
        <th>Name</th>
        <th>Origin</th>
        <th>Destination</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No routes found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
