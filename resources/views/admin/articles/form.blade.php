@extends('layouts.app')
@section('title', 'Article')
@section('content')
<h3 class="page-title">{{ $article->exists ? 'Edit article' : 'New article' }}</h3>
<form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="card"><div class="card-body row g-3">@csrf
    @if($article->exists) @method('PUT') @endif
    <div class="col-12"><label class="form-label">Title</label><input name="title" value="{{ old('title', $article->title) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Summary</label><input name="summary" value="{{ old('summary', $article->summary) }}" class="form-control"></div>
    <div class="col-12"><label class="form-label">Body</label><textarea name="body" rows="12" class="form-control" required>{{ old('body', $article->body) }}</textarea></div>
    <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_published" value="1" id="pb" @checked(old('is_published', $article->is_published))><label class="form-check-label" for="pb">Published</label></div></div>
    <div class="col-12"><button class="btn btn-primary">Save</button></div>
</div></form>
@endsection
