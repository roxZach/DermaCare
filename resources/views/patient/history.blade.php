@extends('layouts.app')
@section('title', 'Medical history')
@section('content')
<h3 class="page-title">Medical history</h3>
<div class="card mb-4"><div class="card-header">Prescriptions</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Date</th><th>Doctor</th><th>Medicines</th><th>Follow-up</th><th></th></tr></thead><tbody>
    @forelse($prescriptions as $rx)
        <tr><td>{{ $rx->created_at->format('d M Y') }}</td><td>{{ $rx->doctor->name }}</td>
            <td class="small">{{ $rx->items->pluck('medicine')->join(', ') }}</td>
            <td>{{ $rx->follow_up_date?->format('d M Y') ?? '-' }}</td>
            <td><a href="{{ route('print.prescription', $rx) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> View</a></td></tr>
    @empty <tr><td colspan="5" class="text-muted">No prescriptions yet.</td></tr> @endforelse
</tbody></table></div></div>
<div class="card mb-4"><div class="card-header">Consultations</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Date</th><th>Doctor</th><th>Complaint</th><th>Diagnosis</th></tr></thead><tbody>
    @forelse($consultations as $c)
        <tr><td>{{ $c->created_at->format('d M Y') }}</td><td>{{ $c->doctor->name }}</td><td class="small">{{ $c->complaint }}</td><td>{{ $c->diagnosis }}</td></tr>
    @empty <tr><td colspan="4" class="text-muted">No consultations yet.</td></tr> @endforelse
</tbody></table></div></div>
<div class="card"><div class="card-header">Biopsy / pathology results</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Ordered</th><th>Test</th><th>Site</th><th>Status</th><th>Result</th></tr></thead><tbody>
    @forelse($biopsies as $b)
        <tr><td>{{ $b->created_at->format('d M Y') }}</td><td>{{ $b->test_type }}</td><td>{{ $b->site }}</td>
            <td>@include('partials.status', ['s' => $b->status === 'reviewed' ? 'reviewed' : 'in_process'])</td>
            <td class="small">@if($b->status === 'reviewed')<strong>Doctor's comment:</strong> {{ $b->doctor_comment }}
                <br><a href="{{ route('files.biopsy', [$b, 'report']) }}" target="_blank">Pathology report (PDF)</a>
                @else <span class="text-muted">Your result will appear here after your dermatologist reviews it.</span>@endif</td></tr>
    @empty <tr><td colspan="5" class="text-muted">No biopsies.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
