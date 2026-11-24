<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Lesion;
use App\Models\LesionAnalysis;
use App\Models\LesionImage;
use App\Models\TeleRequest;
use App\Services\AiService;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Submit Teledermatology Request <<include>> Upload Lesion Images <<include>> AI Image Quality Check.
 * The AI analysis result is queued for the dermatologist and is NOT shown to the patient until reviewed.
 */
class TeleController extends Controller
{
    private function me()
    {
        return auth()->user()->patient ?? abort(403);
    }

    public function index()
    {
        return view('patient.tele.index', ['requests' => $this->me()->teleRequests()->with('doctor.user')->latest()->get()]);
    }

    public function create()
    {
        return view('patient.tele.create', ['doctors' => Doctor::with('user')->get()]);
    }

    public function store(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:150',
            'description' => 'required|string|max:3000',
            'duration' => 'nullable|string|max:50',
            'body_region' => 'required|string|max:50',
            'view' => 'required|in:front,back',
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100',
            'doctor_id' => 'nullable|exists:doctors,id',
            'images' => 'required|array|min:1|max:3',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:8192',
        ], [
            'x.required' => 'Please click on the body diagram to mark where the problem is.',
            'images.required' => 'Please add at least one photo.',
        ]);
        $p = $this->me();

        // 1) Store the photos and run the AI image quality check on each one
        $stored = [];
        $problems = [];
        foreach ($request->file('images') as $i => $file) {
            $path = $file->store("lesions/{$p->id}", 'local');
            $q = $ai->quality($path);
            if (($q['ok'] ?? false) && ! ($q['quality']['ok'] ?? true)) {
                $problems[] = 'Photo ' . ($i + 1) . ': ' . implode(' ', $q['quality']['issues'] ?? ['Image quality is too low.']);
                Storage::disk('local')->delete($path);
                continue;
            }
            $stored[] = ['path' => $path, 'checked' => $q['ok'] ?? false];
        }
        if ($problems) {
            foreach ($stored as $s) {
                Storage::disk('local')->delete($s['path']);
            }
            return back()->withInput()->withErrors(['images' => $problems])
                ->with('error', 'Some photos did not pass the quality check. Please retake them and try again.');
        }

        // 2) Create the lesion on the body map, the images and the request
        $lesion = Lesion::create([
            'patient_id' => $p->id, 'label' => $data['subject'], 'body_region' => $data['body_region'],
            'view' => $data['view'], 'x' => $data['x'], 'y' => $data['y'], 'created_by' => auth()->id(),
            'notes' => 'Added by patient through teledermatology',
        ]);
        $images = [];
        foreach ($stored as $s) {
            $images[] = LesionImage::create([
                'lesion_id' => $lesion->id, 'path' => $s['path'], 'source' => 'teledermatology', 'image_type' => 'clinical',
                'uploaded_by' => auth()->id(), 'quality_ok' => true,
                'quality_message' => $s['checked'] ? 'Passed AI quality check' : 'Quality not checked (AI Engine offline)',
            ]);
        }
        $tele = TeleRequest::create([
            'patient_id' => $p->id, 'doctor_id' => $data['doctor_id'] ?? null, 'lesion_id' => $lesion->id,
            'subject' => $data['subject'], 'description' => $data['description'], 'duration' => $data['duration'] ?? null,
        ]);

        // 3) Queue an AI analysis of the first photo for the dermatologist to review
        $first = $images[0];
        $r = $ai->analyze($first->path);
        if ($r['ok'] ?? false) {
            LesionAnalysis::create([
                'lesion_image_id' => $first->id, 'requested_by' => auth()->id(),
                'model_version' => $r['model_version'] ?? null, 'predicted_class' => $r['predicted_class'] ?? null,
                'confidence' => $r['confidence'] ?? null, 'probabilities' => $r['probabilities'] ?? null,
                'risk_level' => $r['risk_level'] ?? null, 'status' => 'pending_review',
            ]);
        }

        AuditLog::record('tele_request_submitted', "#{$tele->id}");
        $link = route('doctor.tele.show', $tele);
        if ($tele->doctor_id) {
            \App\Models\Alert::send($tele->doctor->user_id, 'New teledermatology request', $tele->subject, $link);
        } else {
            Access::notifyRole('dermatologist', 'New teledermatology request', $tele->subject, $link);
        }
        return redirect()->route('patient.tele.show', $tele)->with('success', 'Your request was sent to our dermatologists.');
    }

    public function show(TeleRequest $tele)
    {
        abort_unless($tele->patient_id === $this->me()->id, 403);
        $tele->load('lesion.images', 'doctor.user');
        return view('patient.tele.show', compact('tele'));
    }
}
