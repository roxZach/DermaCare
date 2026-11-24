@extends('layouts.app')
@section('title', 'Services')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Services &amp; fees</h3>
    <a href="{{ route('admin.services.create') }}" class="btn btn-primary"><i class="bi bi-plus"></i> New service</a></div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Name</th><th>Category</th><th class="text-end">Price</th><th>Active</th><th></th></tr></thead><tbody>
    @foreach($services as $s)
        <tr><td>{{ $s->name }}</td><td>{{ $s->category }}</td><td class="text-end">{{ number_format($s->price, 2) }}</td><td>{!! $s->is_active ? '<i class="bi bi-check-lg text-success"></i>' : '-' !!}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.services.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="POST" action="{{ route('admin.services.destroy', $s) }}" class="d-inline" data-confirm="Delete this service?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
    @endforeach
</tbody></table></div></div>
@endsection
