@extends('layouts.panel')

@section('title', 'Audit Log')
@section('page-label', 'Audit Log')

@section('content')

<div class="page-header">
  <h1 class="page-title">Audit Log</h1>
  <p class="page-subtitle">Review all system activity and changes.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Audit Entries</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Timestamp</th>
        <th>User</th>
        <th>Action</th>
        <th>Resource</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No audit entries found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
