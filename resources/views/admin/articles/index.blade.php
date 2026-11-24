@extends('layouts.app')
@section('title', 'Articles')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Skin-care articles</h3>
    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> New article</a></div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Title</th><th>Published</th><th>Date</th><th></th></tr></thead><tbody>
    @foreach($articles as $a)
        <tr><td>{{ $a->title }}</td><td>{!! $a->is_published ? '<i class="bi bi-check-lg text-success"></i>' : 'Draft' !!}</td><td>{{ $a->created_at->format('d M Y') }}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.articles.edit', $a) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="POST" action="{{ route('admin.articles.destroy', $a) }}" class="d-inline" data-confirm="Delete this article?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
    @endforeach
</tbody></table></div></div>
@endsection
