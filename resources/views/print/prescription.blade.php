@extends('print.layout')
@section('title', 'Prescription')
@section('doc-meta')<strong>PRESCRIPTION</strong><br>#{{ $prescription->id }} · {{ $prescription->created_at->format('d M Y') }}@endsection
@section('body')
@php($p = $prescription->patient)
<p><strong>Patient:</strong> {{ $p->name }} ({{ $p->code }}) · {{ $p->age ? $p->age . ' y' : '' }} {{ ucfirst($p->gender ?? '') }}<br>
<strong>Doctor:</strong> {{ $prescription->doctor->name }}, {{ $prescription->doctor->qualifications }}</p>
@if($p->allergies)<p class="small"><strong>Allergies:</strong> {{ $p->allergies }}</p>@endif
<h2 class="fs-3">&#8478;</h2>
<table class="table"><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead><tbody>
@foreach($prescription->items as $i)<tr><td>{{ $loop->iteration }}</td><td class="fw-semibold">{{ $i->medicine }}</td><td>{{ $i->dosage }}</td><td>{{ $i->frequency }}</td><td>{{ $i->duration }}</td><td>{{ $i->instructions }}</td></tr>@endforeach
</tbody></table>
@if($prescription->advice)<p><strong>Advice:</strong> {{ $prescription->advice }}</p>@endif
@if($prescription->follow_up_date)<p><strong>Follow-up:</strong> {{ $prescription->follow_up_date->format('d M Y') }}</p>@endif
<div class="text-end mt-5">_______________________<br>{{ $prescription->doctor->name }}</div>
@endsection
