@extends('layouts.app')
@section('title', 'Dermatologists')
@section('content')
<h3 class="page-title">Find a dermatologist</h3>
<form class="row g-2 mb-4" method="GET">
    <div class="col-md-6"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search by name or specialty" list="specialties"></div>
    <datalist id="specialties">@foreach($specialties as $s)<option value="{{ $s }}">@endforeach</datalist>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-search"></i> Search</button></div>
</form>
<div class="row g-3">
    @forelse($doctors as $d)
        <div class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-body">
            <div class="d-flex gap-3">
                <i class="bi bi-person-badge fs-1 text-secondary"></i>
                <div>
                    <h6 class="mb-0">{{ $d->name }}</h6>
                    <div class="small text-muted">{{ $d->specialty }}</div>
                    <div class="small">{{ $d->qualifications }}</div>
                    <div class="small">{{ $d->experience_years }} years experience ·
                        @if($d->average_rating)<i class="bi bi-star-fill text-warning"></i> {{ $d->average_rating }} ({{ $d->reviews->count() }})@else No ratings yet @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('doctors.show', $d) }}" class="btn btn-sm btn-outline-primary mt-3">Profile &amp; availability</a>
        </div></div></div>
    @empty
        <p class="text-muted">No dermatologists match your search.</p>
    @endforelse
</div>
@endsection
