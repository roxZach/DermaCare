@extends('layouts.app')
@section('title', 'Service')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<h3 class="page-title">{{ $service->exists ? 'Edit service' : 'New service' }}</h3>
<form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" class="card"><div class="card-body row g-3">@csrf
    @if($service->exists) @method('PUT') @endif
    <div class="col-md-8"><label class="form-label">Name</label><input name="name" value="{{ old('name', $service->name) }}" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Price</label><input type="number" step="0.01" name="price" value="{{ old('price', $service->price) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Category</label><input name="category" value="{{ old('category', $service->category) }}" class="form-control" required list="cats">
        <datalist id="cats"><option value="Medical"><option value="Procedure"><option value="Cosmetic"><option value="Laboratory"></datalist></div>
    <div class="col-md-6 pt-md-4"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $service->is_active))><label class="form-check-label" for="ia">Active</label></div></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $service->description) }}</textarea></div>
    <div class="col-12"><button class="btn btn-primary">Save</button></div>
</div></form></div></div>
@endsection
