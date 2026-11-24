<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModelVersion;
use App\Models\AuditLog;
use App\Models\LesionAnalysis;
use App\Services\AiService;
use Illuminate\Http\Request;

/** Manage AI Model Versions (<<include>> Upload & Activate Model), Monitor AI Performance. */
class AiController extends Controller
{
    public function index(AiService $ai)
    {
        return view('admin.ai.index', ['models' => AiModelVersion::latest()->get(), 'health' => $ai->health()]);
    }

    public function store(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'version' => 'required|string|max:50',
            'architecture' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'model_file' => 'required_without:existing_file|nullable|file|max:512000',
            'existing_file' => 'required_without:model_file|nullable|string|max:150',
            'metrics_file' => 'nullable|file|max:2048',
        ]);
        if ($request->hasFile('model_file')) {
            // Option 1: upload the .pt file through the browser; Laravel forwards it to the AI Engine
            $file = $request->file('model_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, ['pt', 'pth'])) {
                return back()->withInput()->withErrors(['model_file' => 'Upload a PyTorch model file (.pt or .pth).']);
            }
            $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $data['version']) . '.' . $ext;
            $r = $ai->uploadModel($file->getRealPath(), $name);
            if (! ($r['ok'] ?? false)) {
                return back()->withInput()->with('error', 'Upload to the AI Engine failed: ' . ($r['error'] ?? ''));
            }
        } else {
            // Option 2: the file was copied into ai-service/models by hand
            $name = basename($data['existing_file']);
        }
        $metrics = $request->hasFile('metrics_file') ? json_decode(file_get_contents($request->file('metrics_file')->getRealPath()), true) : null;
        AiModelVersion::create([
            'version' => $data['version'], 'architecture' => $data['architecture'], 'file_name' => $name,
            'description' => $data['description'] ?? null, 'metrics' => $metrics, 'is_active' => false,
        ]);
        AuditLog::record('ai_model_uploaded', $name);
        return back()->with('success', 'Model uploaded. Click "Activate" to start using it.');
    }

    public function activate(AiModelVersion $model, AiService $ai)
    {
        $r = $ai->reloadModel($model->file_name);
        if (! ($r['ok'] ?? false)) {
            return back()->with('error', 'The AI Engine could not load this model: ' . ($r['error'] ?? ''));
        }
        AiModelVersion::query()->update(['is_active' => false]);
        $model->update(['is_active' => true]);
        AuditLog::record('ai_model_activated', $model->version);
        return back()->with('success', "Model {$model->version} is now active.");
    }

    public function performance()
    {
        $all = LesionAnalysis::all();
        $reviewed = $all->whereIn('status', ['confirmed', 'overridden']);
        $agreement = $reviewed->count() ? round(100 * $reviewed->where('status', 'confirmed')->count() / $reviewed->count(), 1) : null;
        $classes = array_keys(LesionAnalysis::CLASSES);
        // Rows: AI prediction, columns: doctor's final class (only when the final diagnosis maps to a class)
        $nameToCode = array_flip(LesionAnalysis::CLASSES);
        $matrix = [];
        foreach ($reviewed as $a) {
            $final = $nameToCode[$a->final_diagnosis] ?? 'other';
            $matrix[$a->predicted_class][$final] = ($matrix[$a->predicted_class][$final] ?? 0) + 1;
        }
        return view('admin.ai.performance', [
            'total' => $all->count(),
            'pending' => $all->where('status', 'pending_review')->count(),
            'reviewed' => $reviewed->count(),
            'agreement' => $agreement,
            'avgConfidence' => $all->count() ? round(100 * $all->avg('confidence'), 1) : null,
            'byClass' => $all->groupBy('predicted_class')->map->count(),
            'byRisk' => $all->groupBy('risk_level')->map->count(),
            'byModel' => $all->groupBy('model_version')->map->count(),
            'classes' => $classes,
            'matrix' => $matrix,
            'activeModel' => AiModelVersion::where('is_active', true)->first(),
        ]);
    }
}
