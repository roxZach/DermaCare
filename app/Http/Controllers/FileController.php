<?php

namespace App\Http\Controllers;

use App\Models\BiopsyOrder;
use App\Models\LesionAnalysis;
use App\Models\LesionImage;
use App\Support\Access;
use Illuminate\Support\Facades\Storage;

/**
 * Medical images and reports are stored privately (storage/app/private) and served only
 * to users who are allowed to see them.
 */
class FileController extends Controller
{
    public function lesionImage(LesionImage $image)
    {
        $this->authorizeClinical($image->lesion->patient);
        return $this->serve($image->path);
    }

    public function heatmap(LesionAnalysis $analysis)
    {
        $patient = $analysis->image->lesion->patient;
        $this->authorizeClinical($patient);
        // Patients only see heatmaps of verified results
        if (auth()->user()->hasRole('patient')) {
            abort_unless($analysis->isVerified(), 403);
        }
        abort_unless($analysis->heatmap_path, 404);
        return $this->serve($analysis->heatmap_path);
    }

    public function biopsy(BiopsyOrder $order, string $type)
    {
        $user = auth()->user();
        if ($user->hasRole('patient')) {
            abort_unless($user->patient?->id === $order->patient_id && $order->status === 'reviewed', 403);
        } else {
            abort_unless($user->hasRole('dermatologist', 'lab', 'admin'), 403);
        }
        $path = $type === 'histo' ? $order->histo_image_path : $order->report_path;
        abort_unless($path, 404);
        return $this->serve($path);
    }

    private function authorizeClinical($patient): void
    {
        $user = auth()->user();
        abort_if($user->hasRole('receptionist'), 403);
        Access::authorizePatient($patient);
    }

    private function serve(string $path)
    {
        abort_unless(Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, max-age=3600']);
    }
}
