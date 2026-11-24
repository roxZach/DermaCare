@extends('layouts.app')
@section('title', $article->title)
@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <a href="{{ route('articles') }}" class="small">&larr; All articles</a>
    <h2 class="page-title mt-2">{{ $article->title }}</h2>
    <p class="text-muted">{{ $article->summary }}</p>
    <div class="card"><div class="card-body article-body">{!! nl2br(e($article->body)) !!}</div></div>
</div></div>
@endsection
