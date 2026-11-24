@extends('layouts.app')
@section('title', 'Audit logs')
@section('content')
<h3 class="page-title">Audit logs</h3>
<form class="row g-2 mb-3"><div class="col-md-5"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search action or details (e.g. emr_viewed, login)"></div>
    <div class="col-auto"><button class="btn btn-primary">Search</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
    @foreach($logs as $l)
        <tr><td class="text-nowrap">{{ $l->created_at->format('d M Y H:i:s') }}</td><td>{{ $l->user?->name ?? '-' }}</td><td>{{ $l->user?->role_label }}</td>
            <td><code>{{ $l->action }}</code></td><td class="small">{{ $l->details }}</td><td class="small">{{ $l->ip_address }}</td></tr>
    @endforeach
</tbody></table></div></div>
<div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
@endsection
