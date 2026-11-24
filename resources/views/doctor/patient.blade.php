@extends('layouts.app')
@section('title', $patient->name . ' — EMR')
@section('container', 'container-fluid px-lg-4')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div><h3 class="page-title mb-0">{{ $patient->name }}</h3>
        <div class="text-muted">{{ $patient->code }} · {{ $patient->age ? $patient->age . ' years' : 'age unknown' }} · {{ ucfirst($patient->gender ?? '-') }} · {{ $patient->phone }}</div></div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('doctor.consult.create', [$patient, 'appointment' => $openAppointment?->id]) }}" class="btn btn-primary"><i class="bi bi-clipboard2-pulse"></i> Consultation</a>
        <a href="{{ route('doctor.rx.create', $patient) }}" class="btn btn-outline-primary"><i class="bi bi-capsule"></i> Prescribe</a>
        <a href="{{ route('doctor.biopsy.create', $patient) }}" class="btn btn-outline-primary"><i class="bi bi-eyedropper"></i> Order biopsy</a>
    </div>
</div>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4"><div class="card-header">Skin type &amp; history</div><div class="card-body">
            <form method="POST" action="{{ route('doctor.patients.skin', $patient) }}" class="row g-2">@csrf @method('PUT')
                <div class="col-6"><label class="form-label small">Fitzpatrick skin type</label><select name="fitzpatrick_type" class="form-select form-select-sm">
                    <option value="">-</option>@foreach([1 => 'I – always burns', 2 => 'II – usually burns', 3 => 'III – sometimes burns', 4 => 'IV – rarely burns', 5 => 'V – very rarely burns', 6 => 'VI – never burns'] as $k => $v)
                    <option value="{{ $k }}" @selected($patient->fitzpatrick_type == $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label small">Sun exposure</label><select name="sun_exposure" class="form-select form-select-sm">
                    @foreach(['' => '-', 'low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $k => $v)<option value="{{ $k }}" @selected($patient->sun_exposure === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="family_history_skin_cancer" value="1" id="fh" @checked($patient->family_history_skin_cancer)><label for="fh" class="form-check-label small">Family history of skin cancer</label></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="personal_history_skin_cancer" value="1" id="ph" @checked($patient->personal_history_skin_cancer)><label for="ph" class="form-check-label small">Personal history of skin cancer</label></div></div>
                <div class="col-12"><label class="form-label small">Allergies</label><textarea name="allergies" rows="2" class="form-control form-control-sm">{{ $patient->allergies }}</textarea></div>
                <div class="col-12"><label class="form-label small">Medical history</label><textarea name="medical_history" rows="2" class="form-control form-control-sm">{{ $patient->medical_history }}</textarea></div>
                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Save</button></div>
            </form>
        </div></div>
        <div class="card mb-4"><div class="card-header">Lesion body map</div><div class="card-body">
            @include('partials.bodymap', ['lesions' => $patient->lesions, 'clickable' => true, 'linkTo' => fn ($l) => route('doctor.lesions.show', $l)])
            <form method="POST" action="{{ route('doctor.lesions.store', $patient) }}" class="row g-2 mt-1">@csrf
                <input type="hidden" name="x" id="bm-x"><input type="hidden" name="y" id="bm-y"><input type="hidden" name="view" id="bm-view" value="front">
                <div class="col-6"><input name="label" class="form-control form-control-sm" placeholder="Label, e.g. Mole A" required></div>
                <div class="col-6"><input name="body_region" id="bm-region" class="form-control form-control-sm" placeholder="Body region"></div>
                <div class="col-12"><button class="btn btn-sm btn-teal w-100"><i class="bi bi-plus-circle"></i> Add lesion at selected point</button></div>
            </form>
            <ol class="small mt-3 mb-0">@foreach($patient->lesions as $l)<li><a href="{{ route('doctor.lesions.show', $l) }}">{{ $l->label }}</a>
                <span class="text-muted">{{ $l->body_region }} · {{ $l->images->count() }} image(s)</span></li>@endforeach</ol>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card mb-4"><div class="card-header">Consultations</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Date</th><th>Doctor</th><th>Complaint</th><th>Findings</th><th>Diagnosis</th></tr></thead><tbody>
            @forelse($patient->consultations->sortByDesc('created_at') as $c)
                <tr><td class="text-nowrap">{{ $c->created_at->format('d M Y') }}</td><td>{{ $c->doctor->name }}</td><td class="small">{{ $c->complaint }}</td>
                    <td class="small">{{ $c->findings }}</td><td class="fw-semibold">{{ $c->diagnosis }}</td></tr>
            @empty <tr><td colspan="5" class="text-muted">No consultations yet.</td></tr> @endforelse
        </tbody></table></div></div>
        <div class="card mb-4"><div class="card-header">Prescriptions</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Date</th><th>Medicines</th><th>Follow-up</th><th></th></tr></thead><tbody>
            @forelse($patient->prescriptions->sortByDesc('created_at') as $rx)
                <tr><td>{{ $rx->created_at->format('d M Y') }}</td><td class="small">{{ $rx->items->map(fn ($i) => "{$i->medicine} {$i->dosage}")->join('; ') }}</td>
                    <td>{{ $rx->follow_up_date?->format('d M Y') ?? '-' }}</td>
                    <td><a href="{{ route('print.prescription', $rx) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a></td></tr>
            @empty <tr><td colspan="4" class="text-muted">No prescriptions.</td></tr> @endforelse
        </tbody></table></div></div>
        <div class="card mb-4"><div class="card-header">Biopsies</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Ordered</th><th>Test</th><th>Site</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($patient->biopsyOrders->sortByDesc('created_at') as $b)
                <tr><td>{{ $b->created_at->format('d M Y') }}</td><td>{{ $b->test_type }}</td><td>{{ $b->site }}</td><td>@include('partials.status', ['s' => $b->status])</td>
                    <td><a href="{{ route('doctor.biopsy.show', $b) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
            @empty <tr><td colspan="5" class="text-muted">No biopsies.</td></tr> @endforelse
        </tbody></table></div></div>
        <div class="card"><div class="card-header">Appointments</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Date</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead><tbody>
            @forelse($patient->appointments->sortByDesc('date')->take(10) as $a)
                <tr><td>{{ $a->date->format('d M Y') }} {{ substr($a->time, 0, 5) }}</td><td>{{ $a->doctor->name }}</td><td class="small">{{ $a->reason }}</td><td>@include('partials.status', ['s' => $a->status])</td></tr>
            @empty <tr><td colspan="4" class="text-muted">No appointments.</td></tr> @endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
