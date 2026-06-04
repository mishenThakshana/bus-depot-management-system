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
  .num { text-align: right; }
  tfoot tr { background: #eff4ff; font-weight: 700; }
  tfoot td { padding: 7px 10px; font-size: 10px; border-top: 2px solid #1a56db; }
  .footer { margin-top: 16px; font-size: 9px; color: #999; text-align: right; }
</style>
</head>
<body>

<div class="header">
  <div class="header-top">
    <div>
      <div class="brand">Bus Depot MS</div>
      <div class="report-title">Fuel Consumption Report</div>
      <div class="meta">Generated: {{ now()->format('d M Y, H:i') }}</div>
    </div>
  </div>
  <div class="filters">
    @if (!empty($filters['date_from'])) <span>From: {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }}</span> @endif
    @if (!empty($filters['date_to']))   <span>To: {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }}</span> @endif
    @if (!empty($filters['bus_id']))    <span>Bus: {{ \App\Models\Bus::find($filters['bus_id'])?->registration_number }}</span> @endif
    @if (!empty($filters['driver_id'])) <span>Driver: {{ \App\Models\Driver::find($filters['driver_id'])?->name }}</span> @endif
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Bus</th>
      <th>Driver</th>
      <th>Date</th>
      <th class="num">Litres</th>
      <th class="num">Cost/L (LKR)</th>
      <th class="num">Total Cost (LKR)</th>
      <th class="num">Odometer (km)</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($logs as $i => $log)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td style="font-weight:600;">{{ $log->bus->registration_number }}</td>
        <td>{{ $log->driver?->name ?? '—' }}</td>
        <td>{{ $log->fuel_date->format('d M Y') }}</td>
        <td class="num">{{ number_format((float)$log->litres, 2) }}</td>
        <td class="num">{{ number_format((float)$log->cost_per_litre, 2) }}</td>
        <td class="num">{{ number_format($log->total_cost, 2) }}</td>
        <td class="num">{{ $log->odometer_reading ? number_format($log->odometer_reading) : '—' }}</td>
        <td>{{ $log->notes ?? '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="9" style="text-align:center;color:#6b6b6b;padding:20px;">No records found.</td></tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4">Total</td>
      <td class="num">{{ number_format((float)$totalLitres, 2) }} L</td>
      <td></td>
      <td class="num">LKR {{ number_format($totalCost, 2) }}</td>
      <td colspan="2"></td>
    </tr>
  </tfoot>
</table>

<div class="footer">Bus Depot Management System &mdash; Confidential</div>
</body>
</html>
