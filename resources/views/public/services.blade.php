@extends('layouts.app')
@section('title', 'Services')
@section('content')
<h3 class="page-title">Services &amp; fees</h3>
<p class="text-muted">Treatments and procedures offered at our clinic.</p>
@foreach($services as $category => $items)
    <h5 class="mt-4">{{ $category }}</h5>
    <div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Service</th><th>Description</th><th class="text-end">Fee</th></tr></thead>
        <tbody>@foreach($items as $s)<tr><td class="fw-semibold">{{ $s->name }}</td><td class="small text-muted">{{ $s->description }}</td>
            <td class="text-end">{{ number_format($s->price, 2) }}</td></tr>@endforeach</tbody>
    </table></div></div>
@endforeach
@endsection
