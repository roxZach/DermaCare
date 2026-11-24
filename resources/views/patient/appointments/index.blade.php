@extends('layouts.app')
@section('title', 'My appointments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">My appointments</h3>
    <a href="{{ route('patient.appointments.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> Book appointment</a></div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Date</th><th>Time</th><th>Dermatologist</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($appointments as $a)
        <tr><td>{{ $a->date->format('D, d M Y') }}</td><td>{{ substr($a->time, 0, 5) }}</td><td>{{ $a->doctor->name }}</td>
            <td class="small">{{ $a->reason }}</td><td>@include('partials.status', ['s' => $a->status])</td>
            <td class="text-end text-nowrap">
                @if($a->isUpcoming())
                    <a href="{{ route('patient.appointments.edit', $a) }}" class="btn btn-sm btn-outline-primary">Reschedule</a>
                    <form method="POST" action="{{ route('patient.appointments.cancel', $a) }}" class="d-inline" data-confirm="Cancel this appointment?">@csrf
                        <button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                @elseif($a->status === 'completed' && ! $a->review)
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#rv{{ $a->id }}"><i class="bi bi-star"></i> Rate doctor</button>
                @elseif($a->review)
                    <span class="text-warning small">{!! str_repeat('<i class="bi bi-star-fill"></i>', $a->review->rating) !!}</span>
                @endif
            </td></tr>
        @if($a->status === 'completed' && ! $a->review)
            <tr class="collapse" id="rv{{ $a->id }}"><td colspan="6">
                <form method="POST" action="{{ route('patient.appointments.review', $a) }}" class="row g-2 align-items-center">@csrf
                    <div class="col-auto"><select name="rating" class="form-select form-select-sm">@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>@endfor</select></div>
                    <div class="col"><input name="comment" class="form-control form-control-sm" placeholder="Comment (optional)"></div>
                    <div class="col-auto"><button class="btn btn-sm btn-primary">Submit</button></div>
                </form></td></tr>
        @endif
    @empty <tr><td colspan="6" class="text-muted">No appointments yet.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $appointments->links('pagination::bootstrap-5') }}</div>
@endsection
