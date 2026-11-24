<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\LesionAnalysis;
use App\Models\Prescription;
use App\Support\Access;

/**
 * Printable pages. Users print them or save them as PDF from the browser
 * (use cases: Print / Send Prescription, Download Report (PDF), Print Receipt).
 */
class PrintController extends Controller
{
    public function prescription(Prescription $prescription)
    {
        $prescription->load('items', 'patient', 'doctor.user');
        abort_if(auth()->user()->hasRole('lab'), 403);
        Access::authorizePatient($prescription->patient);
        return view('print.prescription', compact('prescription'));
    }

    public function report(LesionAnalysis $analysis)
    {
        $analysis->load('image.lesion.patient', 'reviewer');
        $patient = $analysis->image->lesion->patient;
        abort_if(auth()->user()->hasRole('receptionist', 'lab'), 403);
        Access::authorizePatient($patient);
        if (auth()->user()->hasRole('patient')) {
            abort_unless($analysis->isVerified(), 403);
        }
        return view('print.report', compact('analysis', 'patient'));
    }

    public function invoice(Invoice $invoice)
    {
        $invoice->load('items', 'payments', 'patient');
        abort_if(auth()->user()->hasRole('lab', 'dermatologist'), 403);
        Access::authorizePatient($invoice->patient);
        return view('print.invoice', compact('invoice'));
    }
}
