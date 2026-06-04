<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; background: #fff; }
  .header { padding: 20px 24px 12px; border-bottom: 2px solid #1a56db; }
  .header-top { display: block; }
  .brand { font-size: 16px; font-weight: 700; color: #1a56db; }
  .report-title { font-size: 13px; font-weight: 600; margin-top: 3px; color: #333; }
  .meta { font-size: 10px; color: #6b6b6b; margin-top: 2px; }
  .filters { margin-top: 10px; font-size: 10px; color: #6b6b6b; }
  .filters span { margin-right: 16px; }

  table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  thead tr { background: #1a56db; color: #fff; }
  thead th { padding: 7px 10px; text-align: left; font-size: 10px; font-weight: 600; }
  tbody tr:nth-child(even) { background: #f7f7f8; }
  tbody td { padding: 6px 10px; border-bottom: 1px solid #e2e2e5; font-size: 10px; }
  .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 9px; font-weight: 600; background: #eff4ff; color: #1a56db; }
  .footer { margin-top: 16px; font-size: 9px; color: #999; text-align: right; }
</style>
</head>
<body>

<div class="header">
  <div class="header-top">
    <div>
      <div class="brand">Bus Depot MS</div>
      <div class="report-title">Maintenance Report</div>
      <div class="meta">Generated: {{ now()->format('d M Y, H:i') }}</div>
    </div>
  </div>
  <div class="filters">
    @if (!empty($filters['date_from'])) <span>From: {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }}</span> @endif
    @if (!empty($filters['date_to']))   <span>To: {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}</span> @endif
    @if (!empty($filters['bus_id']))    <span>Bus: {{ \App\Models\Bus::find($filters['bus_id'])?->registration_number }}</span> @endif
    @if (!empty($filters['type']))      <span>Type: {{ $filters['type'] }}</span> @endif
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Bus</th>
      <th>Type</th>
      <th>Description</th>
      <th>Serviced Date</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($records as $i => $record)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $record->bus->registration_number }}</td>
        <td><span class="badge">{{ $record->maintenance_type }}</span></td>
        <td>{{ $record->description }}</td>
        <td>{{ $record->serviced_date->format('d M Y') }}</td>
        <td>{{ $record->notes ?? '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="6" style="text-align:center;color:#6b6b6b;padding:20px;">No records found.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">Bus Depot Management System &mdash; Confidential</div>
</body>
</html>
