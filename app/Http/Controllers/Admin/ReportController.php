<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use App\Models\ScheduleRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $tab     = $request->query('tab', 'fuel');
        $buses   = Bus::orderBy('registration_number')->get();
        $drivers = Driver::orderBy('name')->get();

        $fuelLogs           = collect();
        $maintenanceRecords = collect();
        $scheduleRuns       = collect();
        $fuelTotals         = null;

        if ($tab === 'fuel') {
            $query = FuelLog::with(['bus', 'driver'])
                ->when($request->date_from, fn ($q, $v) => $q->whereDate('fuel_date', '>=', $v))
                ->when($request->date_to,   fn ($q, $v) => $q->whereDate('fuel_date', '<=', $v))
                ->when($request->bus_id,    fn ($q, $v) => $q->where('bus_id', $v))
                ->when($request->driver_id, fn ($q, $v) => $q->where('driver_id', $v))
                ->orderBy('fuel_date', 'desc')->orderBy('id', 'desc');

            $fuelLogs = $query->paginate(15, ['*'], 'fuel_page')->withQueryString();

            $totals = FuelLog::query()
                ->when($request->date_from, fn ($q, $v) => $q->whereDate('fuel_date', '>=', $v))
                ->when($request->date_to,   fn ($q, $v) => $q->whereDate('fuel_date', '<=', $v))
                ->when($request->bus_id,    fn ($q, $v) => $q->where('bus_id', $v))
                ->when($request->driver_id, fn ($q, $v) => $q->where('driver_id', $v))
                ->selectRaw('SUM(litres) as total_litres, SUM(litres * cost_per_litre) as total_cost')
                ->first();

            $fuelTotals = $totals;
        }

        if ($tab === 'maintenance') {
            $maintenanceRecords = MaintenanceRecord::with('bus')
                ->when($request->date_from, fn ($q, $v) => $q->whereDate('serviced_date', '>=', $v))
                ->when($request->date_to,   fn ($q, $v) => $q->whereDate('serviced_date', '<=', $v))
                ->when($request->bus_id,    fn ($q, $v) => $q->where('bus_id', $v))
                ->when($request->type,      fn ($q, $v) => $q->where('maintenance_type', $v))
                ->orderBy('serviced_date', 'desc')->orderBy('id', 'desc')
                ->paginate(15, ['*'], 'maint_page')->withQueryString();
        }

        if ($tab === 'schedule') {
            $scheduleRuns = ScheduleRun::with(['schedule.route', 'schedule.bus', 'schedule.driver'])
                ->when($request->date_from, fn ($q, $v) => $q->whereDate('run_date', '>=', $v))
                ->when($request->date_to,   fn ($q, $v) => $q->whereDate('run_date', '<=', $v))
                ->when($request->status,    fn ($q, $v) => $q->where('status', $v))
                ->when($request->bus_id,    fn ($q, $v) => $q->whereHas('schedule', fn ($sq) => $sq->where('bus_id', $v)))
                ->orderBy('run_date', 'desc')->orderBy('id', 'desc')
                ->paginate(15, ['*'], 'sched_page')->withQueryString();
        }

        return view('panel.reports', compact(
            'tab', 'buses', 'drivers',
            'fuelLogs', 'maintenanceRecords', 'scheduleRuns', 'fuelTotals'
        ));
    }

    // ── Fuel Export ───────────────────────────────────────────────────────────

    public function exportFuel(Request $request): Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'bus_id'    => ['nullable', 'exists:buses,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'format'    => ['required', 'in:pdf,excel'],
        ]);

        $logs = FuelLog::with(['bus', 'driver'])
            ->when($validated['date_from'] ?? null, fn ($q, $v) => $q->whereDate('fuel_date', '>=', $v))
            ->when($validated['date_to']   ?? null, fn ($q, $v) => $q->whereDate('fuel_date', '<=', $v))
            ->when($validated['bus_id']    ?? null, fn ($q, $v) => $q->where('bus_id', $v))
            ->when($validated['driver_id'] ?? null, fn ($q, $v) => $q->where('driver_id', $v))
            ->orderBy('fuel_date')->orderBy('id')
            ->get();

        $totalLitres = $logs->sum('litres');
        $totalCost   = $logs->sum(fn ($l) => $l->total_cost);
        $filters     = $validated;

        if ($validated['format'] === 'pdf') {
            return Pdf::loadView('reports.fuel-pdf', compact('logs', 'totalLitres', 'totalCost', 'filters'))
                ->setPaper('a4', 'landscape')
                ->download('fuel-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return $this->streamCsv('fuel-report-' . now()->format('Y-m-d') . '.csv', function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Bus', 'Driver', 'Date', 'Litres', 'Cost/L (LKR)', 'Total Cost (LKR)', 'Odometer (km)', 'Notes']);
            foreach ($logs as $i => $log) {
                fputcsv($out, [
                    $i + 1,
                    $log->bus->registration_number,
                    $log->driver?->name ?? '',
                    $log->fuel_date->format('Y-m-d'),
                    number_format((float) $log->litres, 2),
                    number_format((float) $log->cost_per_litre, 2),
                    number_format($log->total_cost, 2),
                    $log->odometer_reading ?? '',
                    $log->notes ?? '',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['', '', '', 'TOTAL', number_format((float) $logs->sum('litres'), 2), '', number_format($logs->sum(fn ($l) => $l->total_cost), 2)]);
            fclose($out);
        });
    }

    // ── Maintenance Export ────────────────────────────────────────────────────

    public function exportMaintenance(Request $request): Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'bus_id'    => ['nullable', 'exists:buses,id'],
            'type'      => ['nullable', 'string'],
            'format'    => ['required', 'in:pdf,excel'],
        ]);

        $records = MaintenanceRecord::with('bus')
            ->when($validated['date_from'] ?? null, fn ($q, $v) => $q->whereDate('serviced_date', '>=', $v))
            ->when($validated['date_to']   ?? null, fn ($q, $v) => $q->whereDate('serviced_date', '<=', $v))
            ->when($validated['bus_id']    ?? null, fn ($q, $v) => $q->where('bus_id', $v))
            ->when($validated['type']      ?? null, fn ($q, $v) => $q->where('maintenance_type', $v))
            ->orderBy('serviced_date')->orderBy('id')
            ->get();

        $filters = $validated;

        if ($validated['format'] === 'pdf') {
            return Pdf::loadView('reports.maintenance-pdf', compact('records', 'filters'))
                ->setPaper('a4', 'landscape')
                ->download('maintenance-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return $this->streamCsv('maintenance-report-' . now()->format('Y-m-d') . '.csv', function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Bus', 'Type', 'Description', 'Serviced Date', 'Notes']);
            foreach ($records as $i => $record) {
                fputcsv($out, [
                    $i + 1,
                    $record->bus->registration_number,
                    $record->maintenance_type,
                    $record->description,
                    $record->serviced_date->format('Y-m-d'),
                    $record->notes ?? '',
                ]);
            }
            fclose($out);
        });
    }

    // ── Schedule Export ───────────────────────────────────────────────────────

    public function exportSchedule(Request $request): Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'bus_id'    => ['nullable', 'exists:buses,id'],
            'status'    => ['nullable', 'in:scheduled,cancelled'],
            'format'    => ['required', 'in:pdf,excel'],
        ]);

        $runs = ScheduleRun::with(['schedule.route', 'schedule.bus', 'schedule.driver'])
            ->when($validated['date_from'] ?? null, fn ($q, $v) => $q->whereDate('run_date', '>=', $v))
            ->when($validated['date_to']   ?? null, fn ($q, $v) => $q->whereDate('run_date', '<=', $v))
            ->when($validated['status']    ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($validated['bus_id']    ?? null, fn ($q, $v) => $q->whereHas('schedule', fn ($sq) => $sq->where('bus_id', $v)))
            ->orderBy('run_date')->orderBy('id')
            ->get();

        $filters   = $validated;
        $scheduled = $runs->where('status', 'scheduled')->count();
        $cancelled = $runs->where('status', 'cancelled')->count();

        if ($validated['format'] === 'pdf') {
            return Pdf::loadView('reports.schedule-pdf', compact('runs', 'filters', 'scheduled', 'cancelled'))
                ->setPaper('a4', 'landscape')
                ->download('schedule-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return $this->streamCsv('schedule-report-' . now()->format('Y-m-d') . '.csv', function () use ($runs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#', 'Run Date', 'Route', 'Bus', 'Driver', 'Departure', 'Arrival', 'Status']);
            foreach ($runs as $i => $run) {
                $schedule = $run->schedule;
                fputcsv($out, [
                    $i + 1,
                    $run->run_date->format('Y-m-d'),
                    $schedule?->route?->name ?? '',
                    $schedule?->bus?->registration_number ?? '',
                    $schedule?->driver?->name ?? '',
                    $schedule ? substr($schedule->departure_time, 0, 5) : '',
                    $schedule ? substr($schedule->arrival_time, 0, 5) : '',
                    ucfirst($run->status),
                ]);
            }
            fclose($out);
        });
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function streamCsv(string $filename, callable $writer): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream($writer, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
