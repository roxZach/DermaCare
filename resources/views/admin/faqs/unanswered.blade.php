@extends('layouts.app')
@section('title', 'Unanswered questions')
@section('content')
<h3 class="page-title">Unanswered &amp; low-rated questions</h3>
<p class="text-muted">Questions the chatbot escalated to staff or that users marked as not helpful. Turn them into FAQs to improve the chatbot.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Date</th><th>Question</th><th>Reason</th><th>Confidence</th><th></th></tr></thead><tbody>
    @forelse($logs as $l)
        <tr><td class="text-nowrap">{{ $l->created_at->format('d M H:i') }}</td><td>{{ $l->question }}</td>
            <td>{!! $l->escalated ? '<span class="badge text-bg-warning">Escalated</span>' : '' !!} {!! $l->rating === -1 ? '<span class="badge text-bg-danger">Not helpful</span>' : '' !!}</td>
            <td>{{ number_format($l->confidence * 100, 0) }}%</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.faqs.create', ['question' => $l->question, 'log' => $l->id]) }}" class="btn btn-sm btn-primary">Create FAQ</a>
                <form method="POST" action="{{ route('admin.faqs.resolve', $l) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Dismiss</button></form></td></tr>
    @empty <tr><td colspan="5" class="text-muted">Nothing to review.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
@endsection
