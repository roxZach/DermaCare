@extends('layouts.app')
@section('title', 'Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Users &amp; roles</h3>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> New user</a></div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ $q }}" class="form-control" placeholder="Name or email"></div>
    <div class="col-auto"><select name="role" class="form-select"><option value="">All roles</option>@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected($role === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div></form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Verified</th><th></th></tr></thead><tbody>
    @foreach($users as $u)
        <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td><span class="badge text-bg-light border">{{ $u->role_label }}</span></td>
            <td>{!! $u->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' !!}</td>
            <td>{!! $u->email_verified_at ? '<i class="bi bi-check-lg text-success"></i>' : '<span class="text-muted small">No</span>' !!}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                @if($u->id !== auth()->id())<form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="d-inline" data-confirm="Delete {{ $u->name }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endif</td></tr>
    @endforeach
</tbody></table></div></div>
<div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
@endsection
