@extends('layouts.app')
@section('title', 'Doctors')
@section('content')
<h3 class="page-title">Doctors &amp; schedules</h3>
<p class="text-muted small">To add a doctor, create a user with the Dermatologist role, then edit the profile here.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Name</th><th>Specialty</th><th>Fee</th><th>Working days</th><th></th></tr></thead><tbody>
    @foreach($doctors as $d)
        <tr><td>{{ $d->name }}</td><td>{{ $d->specialty }}</td><td>{{ number_format($d->consultation_fee, 2) }}</td>
            <td class="small">{{ $d->schedules->map(fn ($s) => substr($s->day_name, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))->join(', ') ?: '-' }}</td>
            <td><a href="{{ route('admin.doctors.edit', $d) }}" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
    @endforeach
</tbody></table></div></div>
@endsection
