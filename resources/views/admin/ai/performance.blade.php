@extends('layouts.app')
@section('title', 'AI performance')
@section('content')
<h3 class="page-title">AI performance monitoring</h3>
<p class="text-muted">How the AI performs in practice, measured against dermatologists' final decisions. Active model: {{ $activeModel?->version ?? 'none registered' }}.</p>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Analyses</div><div class="num">{{ $total }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Waiting for review</div><div class="num">{{ $pending }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Doctor agreement rate</div><div class="num">{{ $agreement !== null ? $agreement . '%' : '-' }}</div><div class="small text-muted">of {{ $reviewed }} reviewed</div></div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Average confidence</div><div class="num">{{ $avgConfidence !== null ? $avgConfidence . '%' : '-' }}</div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-8"><div class="card"><div class="card-header">AI prediction (rows) vs dermatologist's final diagnosis (columns)</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0 text-center">
        <thead><tr><th></th>@foreach($classes as $c)<th>{{ $c }}</th>@endforeach<th>other</th></tr></thead><tbody>
        @foreach($classes as $r)<tr><th>{{ $r }}</th>@foreach(array_merge($classes, ['other']) as $c)
            @php($v = $matrix[$r][$c] ?? 0)<td class="{{ $v && $r === $c ? 'table-success' : ($v ? 'table-warning' : '') }}">{{ $v ?: '' }}</td>@endforeach</tr>@endforeach
    </tbody></table></div><div class="card-body small text-muted">Green = doctor agreed. Yellow = doctor overrode the AI. Overrides are the feedback to use when retraining.</div></div></div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header">Predictions by class</div><ul class="list-group list-group-flush">@foreach($byClass as $k => $v)<li class="list-group-item d-flex justify-content-between"><span>{{ \App\Models\LesionAnalysis::className($k) }}</span><span>{{ $v }}</span></li>@endforeach</ul></div>
        <div class="card mb-3"><div class="card-header">By risk level</div><ul class="list-group list-group-flush">@foreach($byRisk as $k => $v)<li class="list-group-item d-flex justify-content-between"><span>@include('partials.status', ['s' => $k])</span><span>{{ $v }}</span></li>@endforeach</ul></div>
        <div class="card"><div class="card-header">By model version</div><ul class="list-group list-group-flush">@foreach($byModel as $k => $v)<li class="list-group-item d-flex justify-content-between"><span>{{ $k ?: '-' }}</span><span>{{ $v }}</span></li>@endforeach</ul></div>
    </div>
</div>
@endsection
