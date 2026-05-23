@extends('layouts.panel')

@section('title', 'Dashboard')
@section('page-label', 'Dashboard')

@section('content')

<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <p class="page-subtitle">Overview of depot operations.</p>
</div>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Active Buses</div>
    <div class="stat-value">24</div>
    <div class="stat-change stat-change--up">↑ 2 from yesterday</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Drivers on Duty</div>
    <div class="stat-value">18</div>
    <div class="stat-change">of 22 total</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Trips Today</div>
    <div class="stat-value">61</div>
    <div class="stat-change stat-change--up">↑ 5 from yesterday</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Incidents</div>
    <div class="stat-value">1</div>
    <div class="stat-change stat-change--down">↑ 1 open</div>
  </div>
</div>

{{-- Recent Trips table --}}
<div class="table-wrapper">
  <div class="table-header">
    <span class="table-title">Recent Trips</span>
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
        <td style="color:var(--text-muted);font-size:12px;">#TRP-0041</td>
        <td>Route 12 — City Centre</td>
        <td>BUS-07</td>
        <td>D. Perera</td>
        <td style="color:var(--text-muted);font-size:12px;">08:15 AM</td>
        <td><span class="badge badge--green">Completed</span></td>
      </tr>
      <tr>
        <td style="color:var(--text-muted);font-size:12px;">#TRP-0042</td>
        <td>Route 04 — Airport Rd</td>
        <td>BUS-12</td>
        <td>K. Fernando</td>
        <td style="color:var(--text-muted);font-size:12px;">09:00 AM</td>
        <td><span class="badge badge--blue">In Progress</span></td>
      </tr>
      <tr>
        <td style="color:var(--text-muted);font-size:12px;">#TRP-0043</td>
        <td>Route 07 — Suburb Loop</td>
        <td>BUS-03</td>
        <td>S. Ranasinghe</td>
        <td style="color:var(--text-muted);font-size:12px;">09:30 AM</td>
        <td><span class="badge badge--amber">Delayed</span></td>
      </tr>
      <tr>
        <td style="color:var(--text-muted);font-size:12px;">#TRP-0044</td>
        <td>Route 02 — North Terminal</td>
        <td>BUS-19</td>
        <td>A. Silva</td>
        <td style="color:var(--text-muted);font-size:12px;">10:00 AM</td>
        <td><span class="badge">Scheduled</span></td>
      </tr>
      <tr>
        <td style="color:var(--text-muted);font-size:12px;">#TRP-0045</td>
        <td>Route 09 — Harbour View</td>
        <td>BUS-05</td>
        <td>N. Jayawardena</td>
        <td style="color:var(--text-muted);font-size:12px;">10:45 AM</td>
        <td><span class="badge badge--red">Cancelled</span></td>
      </tr>
    </tbody>
  </table>
</div>

@endsection
