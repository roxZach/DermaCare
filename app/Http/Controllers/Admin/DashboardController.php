<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ChatLog;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\LesionAnalysis;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** View Reports & Analytics (<<extend>> Export Report), View Audit Logs, Backup & Restore Data. */
class DashboardController extends Controller
{
    /** Tables included in backups, in an order that respects foreign keys. */
    private const BACKUP_TABLES = ['users', 'patients', 'doctors', 'doctor_schedules', 'services', 'appointments',
        'doctor_reviews', 'consultations', 'lesions', 'lesion_images', 'ai_model_versions', 'lesion_analyses',
        'tele_requests', 'prescriptions', 'prescription_items', 'biopsy_orders', 'invoices', 'invoice_items',
        'payments', 'articles', 'faqs', 'chat_logs', 'alerts', 'audit_logs'];

    public function index()
    {
        $days = collect(range(13, 0))->map(fn ($i) => today()->subDays($i));
        $apptByDay = Appointment::where('date', '>=', $days->first())->where('status', '!=', 'cancelled')
            ->selectRaw('date, count(*) as c')->groupBy('date')->pluck('c', 'date')
            ->mapWithKeys(fn ($c, $d) => [substr($d, 0, 10) => $c]);
        $revByDay = Payment::where('created_at', '>=', $days->first())
            ->get()->groupBy(fn ($p) => $p->created_at->toDateString())->map->sum('amount');

        return view('admin.dashboard', [
            'stats' => [
                'patients' => Patient::count(),
                'appointments_today' => Appointment::whereDate('date', today())->where('status', '!=', 'cancelled')->count(),
                'revenue_month' => Payment::where('created_at', '>=', now()->startOfMonth())->sum('amount'),
                'unpaid' => Invoice::where('status', 'unpaid')->sum('total'),
                'analyses' => LesionAnalysis::count(),
                'chat_questions' => ChatLog::count(),
            ],
            'labels' => $days->map->format('d M')->values(),
            'apptSeries' => $days->map(fn ($d) => $apptByDay[$d->toDateString()] ?? 0)->values(),
            'revSeries' => $days->map(fn ($d) => round($revByDay[$d->toDateString()] ?? 0, 2))->values(),
            'diagnoses' => Consultation::selectRaw('diagnosis, count(*) as c')->whereNotNull('diagnosis')
                ->groupBy('diagnosis')->orderByDesc('c')->take(8)->pluck('c', 'diagnosis'),
            'workload' => Appointment::with('doctor.user')->where('date', '>=', today()->subDays(30))
                ->where('status', 'completed')->get()->groupBy(fn ($a) => $a->doctor->name)->map->count(),
        ]);
    }

    public function export(string $type)
    {
        [$name, $header, $rows] = match ($type) {
            'appointments' => ['appointments', ['ID', 'Date', 'Time', 'Patient', 'Doctor', 'Status', 'Reason'],
                Appointment::with('patient', 'doctor.user')->orderBy('date')->get()->map(fn ($a) => [
                    $a->id, $a->date->toDateString(), substr($a->time, 0, 5), $a->patient->name, $a->doctor->name, $a->status, $a->reason])],
            'invoices' => ['invoices', ['Number', 'Date', 'Patient', 'Subtotal', 'Discount', 'Total', 'Status'],
                Invoice::with('patient')->orderBy('id')->get()->map(fn ($i) => [
                    $i->number, $i->created_at->toDateString(), $i->patient->name, $i->subtotal, $i->discount, $i->total, $i->status])],
            'ai' => ['ai_analyses', ['ID', 'Date', 'Predicted', 'Confidence', 'Risk', 'Status', 'Final diagnosis', 'Model'],
                LesionAnalysis::orderBy('id')->get()->map(fn ($a) => [
                    $a->id, $a->created_at->toDateString(), $a->predicted_class, $a->confidence, $a->risk_level,
                    $a->status, $a->final_diagnosis, $a->model_version])],
            default => abort(404),
        };
        AuditLog::record('report_exported', $type);
        // CSV opens directly in Excel
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, "dermacare_{$name}_" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function audit(Request $request)
    {
        $q = $request->query('q');
        $logs = AuditLog::with('user')->when($q, fn ($w) => $w->where('action', 'like', "%$q%")->orWhere('details', 'like', "%$q%"))
            ->latest()->paginate(30)->withQueryString();
        return view('admin.audit', compact('logs', 'q'));
    }

    public function backup()
    {
        $counts = collect(self::BACKUP_TABLES)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]);
        return view('admin.backup', compact('counts'));
    }

    /** Download all data as a JSON file (uploaded images are in storage/app/private and should be copied separately). */
    public function download()
    {
        $data = ['app' => 'DermaCare AI', 'created_at' => now()->toIso8601String(), 'tables' => []];
        foreach (self::BACKUP_TABLES as $t) {
            $data['tables'][$t] = DB::table($t)->get();
        }
        AuditLog::record('backup_downloaded');
        return response()->streamDownload(fn () => print(json_encode($data, JSON_PRETTY_PRINT)),
            'dermacare_backup_' . now()->format('Ymd_His') . '.json', ['Content-Type' => 'application/json']);
    }

    public function restore(Request $request)
    {
        $request->validate(['backup' => 'required|file|max:51200', 'confirm' => 'accepted']);
        $data = json_decode(file_get_contents($request->file('backup')->getRealPath()), true);
        if (($data['app'] ?? null) !== 'DermaCare AI' || ! isset($data['tables'])) {
            return back()->with('error', 'This is not a valid DermaCare AI backup file.');
        }
        Schema::disableForeignKeyConstraints();
        DB::transaction(function () use ($data) {
            foreach (array_reverse(self::BACKUP_TABLES) as $t) {
                DB::table($t)->delete();
            }
            foreach (self::BACKUP_TABLES as $t) {
                foreach (array_chunk($data['tables'][$t] ?? [], 200) as $chunk) {
                    DB::table($t)->insert($chunk);
                }
            }
        });
        Schema::enableForeignKeyConstraints();
        AuditLog::record('backup_restored', $data['created_at'] ?? '');
        return back()->with('success', 'Backup restored. If your own account changed, please log in again.');
    }
}
