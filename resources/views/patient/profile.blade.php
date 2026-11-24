@extends('layouts.app')
@section('title', 'My profile')
@section('content')
<h3 class="page-title">My profile</h3>
<form method="POST" action="{{ route('patient.profile.update') }}" class="card"><div class="card-body row g-3">@csrf @method('PUT')
    <div class="col-md-6"><label class="form-label">Full name</label><input name="name" value="{{ old('name', $patient->name) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone', $patient->phone) }}" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $patient->date_of_birth?->toDateString()) }}" class="form-control"></div>
    <div class="col-md-4"><label class="form-label">Gender</label><select name="gender" class="form-select">
        @foreach(['' => '-', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v)<option value="{{ $k }}" @selected($patient->gender === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Sun exposure</label><select name="sun_exposure" class="form-select">
        @foreach(['' => '-', 'low' => 'Low (mostly indoors)', 'moderate' => 'Moderate', 'high' => 'High (outdoor work / sport)'] as $k => $v)<option value="{{ $k }}" @selected($patient->sun_exposure === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Address</label><input name="address" value="{{ old('address', $patient->address) }}" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Known allergies</label><textarea name="allergies" rows="3" class="form-control">{{ old('allergies', $patient->allergies) }}</textarea></div>
    <div class="col-md-6"><label class="form-label">Past skin conditions / medical history</label><textarea name="medical_history" rows="3" class="form-control">{{ old('medical_history', $patient->medical_history) }}</textarea></div>
    <div class="col-md-6"><div class="form-check"><input type="checkbox" class="form-check-input" name="family_history_skin_cancer" value="1" id="fh" @checked($patient->family_history_skin_cancer)>
        <label class="form-check-label" for="fh">A family member has had skin cancer</label></div></div>
    <div class="col-md-6"><div class="form-check"><input type="checkbox" class="form-check-input" name="personal_history_skin_cancer" value="1" id="ph" @checked($patient->personal_history_skin_cancer)>
        <label class="form-check-label" for="ph">I have had skin cancer before</label></div></div>
    <div class="col-12"><button class="btn btn-primary">Save profile</button></div>
</div></form>
@endsection
