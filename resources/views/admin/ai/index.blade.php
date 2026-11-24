@extends('layouts.app')
@section('title', 'AI model versions')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">AI model versions</h3>
    <a href="{{ route('admin.ai.performance') }}" class="btn btn-outline-primary"><i class="bi bi-graph-up"></i> Performance</a></div>
<div class="alert {{ ($health['ok'] ?? false) ? 'alert-success' : 'alert-warning' }}">
    @if($health['ok'] ?? false)
        <i class="bi bi-cpu"></i> AI Engine online · loaded model: <strong>{{ $health['model']['version'] ?? 'none' }}</strong>
        ({{ $health['model']['architecture'] ?? '-' }}) · device: {{ $health['device'] ?? '-' }}
        @if(!($health['model']['trained'] ?? false))<br><strong>No trained weights are loaded.</strong> Lesion analysis is disabled until you upload and activate a trained model (see <code>ai-service/README.md</code>).@endif
    @else
        <i class="bi bi-exclamation-triangle"></i> {{ $health['error'] ?? 'AI Engine offline' }}. Start it with <code>uvicorn app.main:app --port 8001</code> inside the <code>ai-service</code> folder.
    @endif
</div>
<div class="row g-4">
    <div class="col-lg-5"><div class="card"><div class="card-header">Upload a trained model</div><div class="card-body">
        <form method="POST" action="{{ route('admin.ai.store') }}" enctype="multipart/form-data" class="row g-2">@csrf
            <div class="col-6"><label class="form-label small">Version name</label><input name="version" class="form-control" placeholder="v1.0-effnetb0" required></div>
            <div class="col-6"><label class="form-label small">Architecture</label><input name="architecture" class="form-control" value="efficientnet_b0" required></div>
            <div class="col-12"><label class="form-label small">Model file (.pt from the training notebook)</label><input type="file" name="model_file" class="form-control"></div>
            <div class="col-12"><label class="form-label small">…or the name of a file already copied into <code>ai-service/models</code></label><input name="existing_file" class="form-control" placeholder="dermacare_effnetb0.pt"></div>
            <div class="col-12"><label class="form-label small">metrics.json (optional)</label><input type="file" name="metrics_file" accept="application/json" class="form-control"></div>
            <div class="col-12"><textarea name="description" rows="2" class="form-control" placeholder="Training notes (dataset, epochs, ...)"></textarea></div>
            <div class="col-12"><button class="btn btn-primary">Upload</button></div>
        </form>
    </div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-header">Versions</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Version</th><th>Architecture</th><th>Balanced acc.</th><th>Mel. sensitivity</th><th>Uploaded</th><th></th></tr></thead><tbody>
        @forelse($models as $m)
            <tr><td>{{ $m->version }} @if($m->is_active)<span class="badge text-bg-success">Active</span>@endif</td><td>{{ $m->architecture }}</td>
                <td>{{ isset($m->metrics['balanced_accuracy']) ? number_format($m->metrics['balanced_accuracy'] * 100, 1) . '%' : '-' }}</td>
                <td>{{ isset($m->metrics['melanoma_sensitivity']) ? number_format($m->metrics['melanoma_sensitivity'] * 100, 1) . '%' : '-' }}</td>
                <td>{{ $m->created_at->format('d M Y') }}</td>
                <td>@unless($m->is_active)<form method="POST" action="{{ route('admin.ai.activate', $m) }}">@csrf<button class="btn btn-sm btn-teal">Activate</button></form>@endunless</td></tr>
        @empty <tr><td colspan="6" class="text-muted">No models uploaded yet.</td></tr> @endforelse
    </tbody></table></div></div></div>
</div>
@endsection
