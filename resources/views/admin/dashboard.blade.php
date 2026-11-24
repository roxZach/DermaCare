@extends('layouts.app')
@section('title', 'Analytics')
@section('container', 'container-fluid px-lg-4')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><h3 class="page-title mb-0">Reports &amp; analytics</h3>
    <div class="dropdown"><button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> Export (Excel CSV)</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('admin.reports.export', 'appointments') }}">Appointments</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.reports.export', 'invoices') }}">Invoices</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.reports.export', 'ai') }}">AI analyses</a></li></ul></div></div>
<div class="row g-3 mb-4">
    @foreach(['patients' => 'Patients', 'appointments_today' => 'Appointments today', 'revenue_month' => 'Revenue this month', 'unpaid' => 'Outstanding (unpaid)', 'analyses' => 'AI analyses', 'chat_questions' => 'Chatbot questions'] as $k => $label)
        <div class="col-6 col-md-4 col-xl-2"><div class="card stat h-100"><div class="card-body"><div class="small text-muted">{{ $label }}</div>
            <div class="num">{{ in_array($k, ['revenue_month', 'unpaid']) ? number_format($stats[$k], 2) : $stats[$k] }}</div></div></div></div>
    @endforeach
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-header">Appointments (last 14 days)</div><div class="card-body"><canvas id="c1" height="150"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Revenue (last 14 days)</div><div class="card-body"><canvas id="c2" height="150"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Most common diagnoses</div><div class="card-body"><canvas id="c3" height="170"></canvas></div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Doctor workload (completed visits, 30 days)</div><div class="card-body"><canvas id="c4" height="170"></canvas></div></div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
const navy = '#16425b', teal = '#2a9d8f';
const opts = { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } };
new Chart(c1, { type: 'bar', data: { labels: @json($labels), datasets: [{ data: @json($apptSeries), backgroundColor: navy }] }, options: opts });
new Chart(c2, { type: 'line', data: { labels: @json($labels), datasets: [{ data: @json($revSeries), borderColor: teal, backgroundColor: 'rgba(42,157,143,.15)', fill: true, tension: .3 }] }, options: opts });
new Chart(c3, { type: 'bar', data: { labels: @json($diagnoses->keys()), datasets: [{ data: @json($diagnoses->values()), backgroundColor: teal }] }, options: { ...opts, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } } });
new Chart(c4, { type: 'bar', data: { labels: @json($workload->keys()), datasets: [{ data: @json($workload->values()), backgroundColor: navy }] }, options: opts });
</script>
@endpush
