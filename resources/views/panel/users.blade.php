@extends('layouts.panel')

@section('title', 'User Management')
@section('page-label', 'User Management')

@section('content')

<div class="page-header">
  <h1 class="page-title">User Management</h1>
  <p class="page-subtitle">Manage system users and their roles.</p>
</div>

<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">All Users</span>
  </div>
  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px;">No users found.</td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
