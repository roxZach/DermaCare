@extends('layouts.app')
@section('title', 'Backup & restore')
@section('content')
<h3 class="page-title">Backup &amp; restore</h3>
<div class="row g-4">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Create backup</div><div class="card-body">
        <p>Download all records as a JSON file. Uploaded images and reports are stored in <code>storage/app/private</code>; copy that folder as well.</p>
        <a href="{{ route('admin.backup.download') }}" class="btn btn-primary"><i class="bi bi-download"></i> Download backup</a>
        <table class="table table-sm mt-3 mb-0"><tbody>@foreach($counts as $t => $c)<tr><td><code>{{ $t }}</code></td><td class="text-end">{{ $c }}</td></tr>@endforeach</tbody></table>
    </div></div></div>
    <div class="col-lg-6"><div class="card border-danger"><div class="card-header text-danger">Restore from backup</div><div class="card-body">
        <p class="small">This <strong>replaces all current data</strong> with the contents of the backup file.</p>
        <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" data-confirm="All current data will be replaced. Continue?">@csrf
            <input type="file" name="backup" accept="application/json" class="form-control mb-2" required>
            <div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="confirm" value="1" id="cf" required><label class="form-check-label" for="cf">I understand that current data will be replaced</label></div>
            <button class="btn btn-danger">Restore</button></form>
    </div></div></div>
</div>
@endsection
