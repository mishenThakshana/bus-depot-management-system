@extends('layouts.panel')

@section('title', 'Fuel & Maintenance')
@section('page-label', 'Fuel & Maintenance')

@section('content')

<div class="page-header">
  <h1 class="page-title">Fuel &amp; Maintenance</h1>
  <p class="page-subtitle">Monitor fuel usage and maintenance activity.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Fuel &amp; Maintenance Records</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Record ID</th>
        <th>Bus</th>
        <th>Type</th>
        <th>Date</th>
        <th>Cost</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;">No records found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
