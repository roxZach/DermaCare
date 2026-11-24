@extends('layouts.app')
@section('title', 'Skin-care articles')
@section('content')
<h3 class="page-title">Skin-care articles</h3>
<div class="row g-3">
    @foreach($articles as $a)
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <h6><a href="{{ route('articles.show', $a) }}" class="text-decoration-none">{{ $a->title }}</a></h6>
            <p class="small text-muted">{{ $a->summary }}</p>
            <span class="small text-muted">{{ $a->created_at->format('d M Y') }}</span>
        </div></div></div>
    @endforeach
</div>
<div class="mt-3">{{ $articles->links('pagination::bootstrap-5') }}</div>
@endsection
