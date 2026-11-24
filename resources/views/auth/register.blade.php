@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card"><div class="card-body p-4">
        <h4 class="page-title">Create a patient account</h4>
        <p class="small text-muted">We will send a 6-digit code to your email to verify it.</p>
        <form method="POST" action="{{ route('register') }}" class="row g-3">@csrf
            <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Gender</label>
                <select name="gender" class="form-select" required><option value="">Choose</option>
                    @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}" @selected(old('gender') === $k)>{{ $v }}</option>@endforeach
                </select></div>
            <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
            <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
            <div class="col-12"><button class="btn btn-primary">Register</button>
                <span class="small ms-2">Already registered? <a href="{{ route('login') }}">Log in</a></span></div>
        </form>
    </div></div>
</div></div>
@endsection
