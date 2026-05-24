@extends('layouts.panel')

@section('title', 'Reports')
@section('page-label', 'Reports')

@section('content')

<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <p class="page-subtitle">Generate and export depot reports.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Available Reports</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Report Name</th>
        <th>Type</th>
        <th>Last Generated</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px;">No reports available.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
