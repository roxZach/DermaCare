@extends('layouts.app')
@section('title', 'Patients')
@section('content')
<h3 class="page-title">Patients</h3>
<form class="row g-2 mb-3"><div class="col-md-6"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search by name, phone, email or patient ID (P-00012)"></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>ID</th><th>Name</th><th>Age / gender</th><th>Phone</th><th>Skin type</th><th></th></tr></thead><tbody>
    @forelse($patients as $p)
        <tr><td>{{ $p->code }}</td><td>{{ $p->name }}</td><td>{{ $p->age ?? '-' }} / {{ ucfirst($p->gender ?? '-') }}</td><td>{{ $p->phone }}</td>
            <td>{{ $p->fitzpatrick_type ? 'Type ' . $p->fitzpatrick_type : '-' }}</td>
            <td class="text-end"><a href="{{ route('doctor.patients.show', $p) }}" class="btn btn-sm btn-outline-primary">Open EMR</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No patients found.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $patients->links('pagination::bootstrap-5') }}</div>
@endsection
