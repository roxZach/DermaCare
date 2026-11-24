<?php

namespace App\Http\Controllers\Doctor;

use App\Models\AuditLog;
use App\Models\Lesion;
use App\Models\LesionAnalysis;
use App\Models\LesionImage;
use App\Models\Patient;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Manage Lesion Body Map, Capture Lesion Image, Request AI Lesion Analysis,
 * Review & Confirm AI Result (<<extend>> Override AI Diagnosis), Compare Lesion Images Over Time,
 * Generate Grad-CAM Heatmap.
 */
class LesionController extends DoctorController
{
    // Mark a new lesion on the body map
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'body_region' => 'nullable|string|max:50',
            'view' => 'required|in:front,back',
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ], ['x.required' => 'Click on the body map first to choose where the lesion is.']);
        $lesion = $patient->lesions()->create($data + ['created_by' => auth()->id()]);
        AuditLog::record('lesion_marked', "{$patient->code} lesion #{$lesion->id}");
        return redirect()->route('doctor.lesions.show', $lesion)->with('success', 'Lesion added to the body map. Now upload an image.');
    }

    public function show(Lesion $lesion)
    {
        $lesion->load('patient', 'images.analyses', 'images.uploader', 'biopsyOrders');
        return view('doctor.lesion', compact('lesion'));
    }

    // Use case: Capture Lesion Image (with AI image quality check, alternative flow 4a)
    public function upload(Request $request, Lesion $lesion, AiService $ai)
    {
        $data = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:10240',
            'image_type' => 'required|in:dermoscopic,clinical',
        ]);
        $path = $request->file('image')->store("lesions/{$lesion->patient_id}", 'local');
        $q = $ai->quality($path);
        if (($q['ok'] ?? false) && ! ($q['quality']['ok'] ?? true)) {
            Storage::disk('local')->delete($path);
            return back()->with('error', 'Image rejected by the quality check: ' . implode(' ', $q['quality']['issues'] ?? []) . ' Please retake the photo.');
        }
        $image = LesionImage::create([
            'lesion_id' => $lesion->id, 'path' => $path, 'source' => 'clinic', 'image_type' => $data['image_type'],
            'uploaded_by' => auth()->id(), 'quality_ok' => true,
            'quality_message' => ($q['ok'] ?? false) ? 'Passed AI quality check' : 'Quality not checked (AI Engine offline)',
        ]);
        AuditLog::record('lesion_image_uploaded', "lesion #{$lesion->id} image #{$image->id}");
        if ($request->boolean('analyze_now')) {
            return $this->analyze($image, $ai);
        }
        return back()->with('success', 'Image saved.');
    }

    // Use case: Request AI Lesion Analysis  (Analyze Skin Lesion <<include>> quality, preprocess, classify, risk)
    public function analyze(LesionImage $image, AiService $ai)
    {
        $r = $ai->analyze($image->path);
        if (! ($r['ok'] ?? false)) {
            return back()->with('error', $r['error'] ?? 'Analysis failed.');
        }
        if (isset($r['quality']) && ! ($r['quality']['ok'] ?? true)) {
            return back()->with('error', 'Image quality is too low for analysis: ' . implode(' ', $r['quality']['issues'] ?? []));
        }
        $analysis = LesionAnalysis::create([
            'lesion_image_id' => $image->id, 'requested_by' => auth()->id(),
            'model_version' => $r['model_version'] ?? null, 'predicted_class' => $r['predicted_class'],
            'confidence' => $r['confidence'], 'probabilities' => $r['probabilities'],
            'risk_level' => $r['risk_level'], 'status' => 'pending_review',
        ]);
        AuditLog::record('ai_analysis_requested', "image #{$image->id} -> analysis #{$analysis->id}");
        return redirect()->route('doctor.analyses.show', $analysis);
    }

    public function pending()
    {
        $analyses = LesionAnalysis::with('image.lesion.patient')->where('status', 'pending_review')->latest()->paginate(20);
        return view('doctor.analyses', compact('analyses'));
    }

    public function analysis(LesionAnalysis $analysis)
    {
        $analysis->load('image.lesion.patient', 'reviewer');
        return view('doctor.analysis', compact('analysis'));
    }

    // Use case: Generate Grad-CAM Heatmap (<<extend>> Analyze Skin Lesion)
    public function heatmap(LesionAnalysis $analysis, AiService $ai)
    {
        $r = $ai->gradcam($analysis->image->path);
        if (! ($r['ok'] ?? false) || empty($r['heatmap_png'])) {
            return back()->with('error', $r['error'] ?? 'Could not generate the heatmap.');
        }
        $path = "heatmaps/analysis-{$analysis->id}.png";
        Storage::disk('local')->put($path, base64_decode($r['heatmap_png']));
        $analysis->update(['heatmap_path' => $path]);
        return back()->with('success', 'Heatmap generated.');
    }

    // Use case: Review & Confirm AI Result (<<include>> Save Result to Patient EMR, <<extend>> Override)
    public function review(Request $request, LesionAnalysis $analysis)
    {
        $data = $request->validate([
            'decision' => 'required|in:confirm,override',
            'final_class' => 'required_if:decision,override|nullable|string|max:100',
            'final_other' => 'nullable|string|max:150',
            'doctor_note' => 'nullable|string|max:2000',
        ]);
        if ($data['decision'] === 'confirm') {
            $final = LesionAnalysis::className($analysis->predicted_class);
            $status = 'confirmed';
        } else {
            $final = $data['final_class'] === 'other' ? ($data['final_other'] ?: 'Other')
                : LesionAnalysis::className($data['final_class']);
            $status = 'overridden';
        }
        $analysis->update([
            'status' => $status, 'final_diagnosis' => $final, 'doctor_note' => $data['doctor_note'] ?? null,
            'reviewed_by' => auth()->id(), 'reviewed_at' => now(),
        ]);
        AuditLog::record('ai_result_' . $status, "analysis #{$analysis->id}: {$final}");
        $patient = $analysis->image->lesion->patient;
        \App\Models\Alert::send($patient->user_id, 'Your skin analysis report is ready',
            'A dermatologist has reviewed your lesion image.', route('patient.reports'));
        return redirect()->route('doctor.lesions.show', $analysis->image->lesion_id)->with('success', 'Result saved to the patient record.');
    }

    // Use case: Compare Lesion Images Over Time (<<extend>> Manage Lesion Body Map)
    public function compare(Request $request, Lesion $lesion)
    {
        $images = $lesion->images()->with('analyses')->get()->sortBy('created_at')->values();
        $a = $images->firstWhere('id', (int) $request->query('a')) ?? $images->first();
        $b = $images->firstWhere('id', (int) $request->query('b')) ?? $images->last();
        return view('doctor.compare', compact('lesion', 'images', 'a', 'b'));
    }
}
