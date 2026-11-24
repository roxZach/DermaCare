@extends('layouts.app')
@section('title', 'Verify your email')
@section('content')
<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
    <div class="card"><div class="card-body p-4">
        <h4 class="page-title">Verify your email</h4>
        <p class="small">Enter the 6-digit code sent to <strong>{{ $user->email }}</strong>. The code expires in 10 minutes.</p>
        @if($demoCode)
            <div class="alert alert-info small"><i class="bi bi-info-circle"></i> Demo mode (no mail server configured):
                your code is <strong class="fs-6">{{ $demoCode }}</strong>. It is also written to <code>storage/logs/laravel.log</code>.</div>
        @endif
        <form method="POST" action="{{ route('otp.verify') }}">@csrf
            <input name="code" inputmode="numeric" maxlength="6" class="form-control form-control-lg text-center mb-3" placeholder="000000" required autofocus>
            <button class="btn btn-primary w-100">Verify</button>
        </form>
        <form method="POST" action="{{ route('otp.resend') }}" class="mt-2">@csrf<button class="btn btn-link btn-sm p-0">Send a new code</button></form>
    </div></div>
</div></div>
@endsection
