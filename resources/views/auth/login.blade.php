@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
    <div class="card"><div class="card-body p-4">
        <h4 class="page-title">Log in</h4>
        <form method="POST" action="{{ route('login') }}">@csrf
            <div class="mb-3"><label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
            <div class="mb-3"><label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label></div>
            <button class="btn btn-primary w-100">Log in</button>
        </form>
        <p class="small mt-3 mb-0">New patient? <a href="{{ route('register') }}">Create an account</a></p>
    </div></div>
    @if(config('app.debug'))
    <div class="card mt-3"><div class="card-body small">
        <strong>Demo accounts</strong> (password: <code>password</code>)
        <ul class="mb-0 mt-1">
            <li>admin@dermacare.test</li><li>dr.rahman@dermacare.test (Dermatologist)</li>
            <li>reception@dermacare.test</li><li>lab@dermacare.test</li><li>patient@dermacare.test</li>
        </ul>
    </div></div>
    @endif
</div></div>
@endsection
