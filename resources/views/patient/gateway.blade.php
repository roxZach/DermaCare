@extends('layouts.app')
@section('title', 'Payment')
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
    <div class="card"><div class="card-header d-flex justify-content-between"><span><i class="bi bi-lock"></i> Secure payment</span>
        <span class="badge text-bg-warning">Sandbox / demo gateway</span></div>
    <div class="card-body">
        <p class="mb-1">Invoice <strong>{{ $invoice->number }}</strong></p>
        <p class="fs-4 fw-bold">{{ number_format($invoice->total - $invoice->paid_amount, 2) }}</p>
        <div class="alert alert-info small">This is a simulated payment page for the project demo. No real money is charged.
            Use any name and any 16-digit number, e.g. 4242 4242 4242 4242.</div>
        <form method="POST" action="{{ route('patient.bills.confirm', $invoice) }}" class="row g-2">@csrf
            <div class="col-12"><input name="card_name" class="form-control" placeholder="Name on card" required></div>
            <div class="col-12"><input name="card_number" class="form-control" placeholder="Card number (digits only)" inputmode="numeric" required></div>
            <div class="col-6"><input class="form-control" placeholder="MM/YY"></div><div class="col-6"><input class="form-control" placeholder="CVC"></div>
            <div class="col-12"><button class="btn btn-teal w-100">Pay now</button></div>
        </form>
    </div></div>
</div></div>
@endsection
