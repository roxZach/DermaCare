@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Notifications</h3>
    <form method="POST" action="{{ route('alerts.readAll') }}">@csrf<button class="btn btn-outline-secondary btn-sm">Mark all as read</button></form></div>
<div class="card"><ul class="list-group list-group-flush">
    @forelse($alerts as $a)
        <a href="{{ route('alerts.open', $a) }}" class="list-group-item list-group-item-action {{ $a->read_at ? '' : 'fw-semibold' }}">
            <div class="d-flex justify-content-between"><span>{{ $a->title }}</span><span class="small text-muted">{{ $a->created_at->diffForHumans() }}</span></div>
            <div class="small text-muted fw-normal">{{ $a->message }}</div>
        </a>
    @empty <li class="list-group-item text-muted">No notifications.</li> @endforelse
</ul></div>
<div class="mt-3">{{ $alerts->links('pagination::bootstrap-5') }}</div>
@endsection
