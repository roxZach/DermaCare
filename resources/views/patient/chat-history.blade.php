@extends('layouts.app')
@section('title', 'Chat history')
@section('content')
<h3 class="page-title">Chat history</h3>
<div class="card"><ul class="list-group list-group-flush">
    @forelse($logs as $l)
        <li class="list-group-item"><div class="small text-muted">{{ $l->created_at->format('d M Y H:i') }}</div>
            <div><strong>You:</strong> {{ $l->question }}</div>
            <div class="small" style="white-space:pre-line"><strong>Assistant:</strong> {{ $l->answer }}</div></li>
    @empty <li class="list-group-item text-muted">You haven't asked the assistant anything yet. Use the chat button in the corner.</li> @endforelse
</ul></div>
<div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
@endsection
