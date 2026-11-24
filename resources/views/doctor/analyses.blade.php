@extends('layouts.app')
@section('title', 'AI results to review')
@section('content')
<h3 class="page-title">AI results waiting for review</h3>
<p class="text-muted">Patients only see a result after a dermatologist confirms or overrides it.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Image</th><th>Patient</th><th>Lesion</th><th>AI prediction</th><th>Risk</th><th>Date</th><th></th></tr></thead><tbody>
    @forelse($analyses as $a)
        <tr><td style="width:70px"><img src="{{ route('files.lesion', $a->image) }}" class="lesion-thumb" alt=""></td>
            <td>{{ $a->image->lesion->patient->name }}</td><td>{{ $a->image->lesion->label }}</td>
            <td>{{ \App\Models\LesionAnalysis::className($a->predicted_class) }} ({{ round($a->confidence * 100) }}%)</td>
            <td>@include('partials.status', ['s' => $a->risk_level])</td><td>{{ $a->created_at->format('d M H:i') }}</td>
            <td><a href="{{ route('doctor.analyses.show', $a) }}" class="btn btn-sm btn-primary">Review</a></td></tr>
    @empty <tr><td colspan="7" class="text-muted">Nothing to review.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $analyses->links('pagination::bootstrap-5') }}</div>
@endsection
