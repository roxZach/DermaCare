@extends('layouts.app')
@section('title', $user->exists ? 'Edit user' : 'New user')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<h3 class="page-title">{{ $user->exists ? 'Edit user' : 'New user' }}</h3>
<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card"><div class="card-body row g-3">@csrf
    @if($user->exists) @method('PUT') @endif
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select">@foreach(\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label><input type="password" name="password" class="form-control" {{ $user->exists ? '' : 'required' }} minlength="8"></div>
    <div class="col-md-6 pt-md-4"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $user->is_active))><label class="form-check-label" for="ia">Account active</label></div></div>
    <div class="col-12 small text-muted">Permissions come from the role: each role only sees its own menu and pages. A Dermatologist account automatically gets a doctor profile (edit it under Clinic &gt; Doctors).</div>
    <div class="col-12"><button class="btn btn-primary">Save</button> <a href="{{ route('admin.users.index') }}" class="btn btn-link">Cancel</a></div>
</div></form>
</div></div>
@endsection
