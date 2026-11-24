@extends('layouts.app')
@section('title', 'Patients')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Patients</h3>
    <a href="{{ route('reception.patients.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register walk-in patient</a></div>
<form class="row g-2 mb-3"><div class="col-md-6"><input name="q" value="{{ $q }}" class="form-control" placeholder="Name, phone, email or patient ID"></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Online account</th><th></th></tr></thead><tbody>
    @forelse($patients as $p)
        <tr><td>{{ $p->code }}</td><td>{{ $p->name }}</td><td>{{ $p->phone }}</td><td>{{ $p->email }}</td><td>{!! $p->user_id ? '<i class="bi bi-check-lg text-success"></i>' : '<span class="text-muted">Walk-in</span>' !!}</td>
            <td class="text-end text-nowrap"><a href="{{ route('reception.patients.show', $p) }}" class="btn btn-sm btn-outline-primary">Open</a>
                <a href="{{ route('reception.appointments.create', ['patient' => $p->id]) }}" class="btn btn-sm btn-primary">Book</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No patients found.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $patients->links('pagination::bootstrap-5') }}</div>
@endsection
