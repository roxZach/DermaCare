@extends('layouts.app')
@section('title', 'Report history')
@section('content')
<h3 class="page-title">Report history</h3>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Completed</th><th>Patient</th><th>Test</th><th>Doctor</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($orders as $o)
        <tr><td>{{ $o->completed_at?->format('d M Y H:i') }}</td><td>{{ $o->patient->name }}</td><td>{{ $o->test_type }}</td><td>{{ $o->doctor->name }}</td>
            <td>@include('partials.status', ['s' => $o->status])</td><td><a href="{{ route('files.biopsy', [$o, 'report']) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Report</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No reports yet.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
