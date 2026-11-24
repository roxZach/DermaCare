@extends('layouts.app')
@section('title', 'Home')
@section('content')
<section class="dc-hero p-4 p-md-5 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="display-6 fw-bold">Specialist skin care, supported by AI</h1>
            <p class="lead">DermaCare AI is a dermatology clinic system with online booking, teledermatology and an AI tool
                that helps our dermatologists analyse skin lesion images.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg">Create an account</a>
                <a href="{{ route('doctors') }}" class="btn btn-outline-light btn-lg">Find a dermatologist</a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="bg-white text-dark rounded-4 p-4">
                <h5 class="mb-3"><i class="bi bi-stars text-warning"></i> How our AI helps</h5>
                <ol class="small mb-0 ps-3">
                    <li class="mb-2">You or your doctor upload a photo of a skin lesion.</li>
                    <li class="mb-2">The AI checks the photo quality and estimates the lesion type and risk level.</li>
                    <li class="mb-2">A dermatologist reviews the result and confirms or corrects it.</li>
                    <li>You receive a verified report in your account.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<div class="d-flex justify-content-between align-items-end mb-2"><h4 class="page-title mb-0">Our services</h4>
    <a href="{{ route('services') }}">All services &rarr;</a></div>
<div class="row g-3 mb-4">
    @foreach($services as $s)
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <span class="badge text-bg-light border mb-2">{{ $s->category }}</span>
            <h6>{{ $s->name }}</h6><p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($s->description, 110) }}</p>
            <div class="fw-semibold">{{ number_format($s->price, 2) }}</div>
        </div></div></div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-end mb-2"><h4 class="page-title mb-0">Our dermatologists</h4>
    <a href="{{ route('doctors') }}">All doctors &rarr;</a></div>
<div class="row g-3 mb-4">
    @foreach($doctors as $d)
        <div class="col-md-3"><div class="card h-100 text-center"><div class="card-body">
            <i class="bi bi-person-badge fs-1 text-secondary"></i>
            <h6 class="mt-2 mb-0">{{ $d->name }}</h6><div class="small text-muted">{{ $d->specialty }}</div>
            <a href="{{ route('doctors.show', $d) }}" class="btn btn-sm btn-outline-primary mt-2">View profile</a>
        </div></div></div>
    @endforeach
</div>

<h4 class="page-title">Skin-care articles</h4>
<div class="row g-3">
    @foreach($articles as $a)
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h6><a href="{{ route('articles.show', $a) }}" class="text-decoration-none">{{ $a->title }}</a></h6>
            <p class="small text-muted mb-0">{{ $a->summary }}</p>
        </div></div></div>
    @endforeach
</div>
@endsection
