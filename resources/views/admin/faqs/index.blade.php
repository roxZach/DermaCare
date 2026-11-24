@extends('layouts.app')
@section('title', 'FAQ knowledge base')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><h3 class="page-title mb-0">FAQ knowledge base</h3>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.faqs.rebuild') }}">@csrf<button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Rebuild chatbot index</button></form>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> New FAQ</a></div></div>
<div class="alert {{ ($ai['ok'] ?? false) ? 'alert-success' : 'alert-warning' }} small">
    @if($ai['ok'] ?? false) <i class="bi bi-cpu"></i> AI Engine online · chatbot engine: <strong>{{ $ai['chatbot']['engine'] ?? '?' }}</strong> · {{ $ai['chatbot']['count'] ?? 0 }} FAQs indexed.
    @else <i class="bi bi-exclamation-triangle"></i> AI Engine offline: the chatbot is using the simple PHP fallback matcher. Start the Python service (see README). @endif
</div>
<form class="row g-2 mb-3"><div class="col-md-5"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search FAQs"></div><div class="col-auto"><button class="btn btn-outline-primary">Search</button></div></form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Category</th><th>Question</th><th>Paraphrases</th><th>Active</th><th></th></tr></thead><tbody>
    @foreach($faqs as $f)
        <tr><td>{{ $f->category }}</td><td>{{ $f->question }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($f->answer, 90) }}</div></td>
            <td>{{ count(array_filter(preg_split('/\r?\n/', (string) $f->paraphrases))) }}</td><td>{!! $f->is_active ? '<i class="bi bi-check-lg text-success"></i>' : '-' !!}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.faqs.edit', $f) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="POST" action="{{ route('admin.faqs.destroy', $f) }}" class="d-inline" data-confirm="Delete this FAQ?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
    @endforeach
</tbody></table></div></div>
@endsection
