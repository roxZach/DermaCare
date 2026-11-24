@extends('layouts.app')
@section('title', 'Register walk-in patient')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<h3 class="page-title">Register walk-in patient</h3>
<form method="POST" action="{{ route('reception.patients.store') }}" class="card"><div class="card-body row g-3">@csrf
    <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Email (optional)</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">-</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
    <div class="col-12"><label class="form-label">Address</label><input name="address" value="{{ old('address') }}" class="form-control"></div>
    <div class="col-12"><button class="btn btn-primary">Register and book appointment</button></div>
</div></form>
</div></div>
@endsection
