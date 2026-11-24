@extends('layouts.app')
@section('title', 'Edit doctor')
@section('content')
<h3 class="page-title">{{ $doctor->name }}</h3>
<form method="POST" action="{{ route('admin.doctors.update', $doctor) }}" class="card"><div class="card-body row g-3">@csrf @method('PUT')
    <div class="col-md-6"><label class="form-label">Specialty</label><input name="specialty" value="{{ old('specialty', $doctor->specialty) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Qualifications</label><input name="qualifications" value="{{ old('qualifications', $doctor->qualifications) }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Experience (years)</label><input type="number" name="experience_years" value="{{ old('experience_years', $doctor->experience_years) }}" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Consultation fee</label><input type="number" step="0.01" name="consultation_fee" value="{{ old('consultation_fee', $doctor->consultation_fee) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Bio</label><textarea name="bio" rows="3" class="form-control">{{ old('bio', $doctor->bio) }}</textarea></div>
    <div class="col-12"><h6>Weekly schedule</h6>
        <template id="sch-tpl"><div class="row g-2 mb-2 form-row-item">
            <div class="col-md-3"><select name="schedules[__I__][day_of_week]" class="form-select">@foreach(\App\Models\DoctorSchedule::DAYS as $i => $day)<option value="{{ $i }}">{{ $day }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="time" name="schedules[__I__][start_time]" class="form-control" value="09:00" required></div>
            <div class="col-md-2"><input type="time" name="schedules[__I__][end_time]" class="form-control" value="13:00" required></div>
            <div class="col-md-2"><input type="number" name="schedules[__I__][slot_minutes]" class="form-control" value="20" required title="Minutes per slot"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row w-100"><i class="bi bi-x"></i></button></div>
        </div></template>
        <div class="row small text-muted"><div class="col-md-3">Day</div><div class="col-md-2">Start</div><div class="col-md-2">End</div><div class="col-md-2">Minutes per slot</div></div>
        <div id="sch-rows">
            @foreach($doctor->schedules as $i => $s)
                <div class="row g-2 mb-2 form-row-item">
                    <div class="col-md-3"><select name="schedules[{{ $i }}][day_of_week]" class="form-select">@foreach(\App\Models\DoctorSchedule::DAYS as $k => $day)<option value="{{ $k }}" @selected($s->day_of_week == $k)>{{ $day }}</option>@endforeach</select></div>
                    <div class="col-md-2"><input type="time" name="schedules[{{ $i }}][start_time]" class="form-control" value="{{ substr($s->start_time, 0, 5) }}" required></div>
                    <div class="col-md-2"><input type="time" name="schedules[{{ $i }}][end_time]" class="form-control" value="{{ substr($s->end_time, 0, 5) }}" required></div>
                    <div class="col-md-2"><input type="number" name="schedules[{{ $i }}][slot_minutes]" class="form-control" value="{{ $s->slot_minutes }}" required></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row w-100"><i class="bi bi-x"></i></button></div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-add-row="#sch-tpl" data-target="#sch-rows"><i class="bi bi-plus"></i> Add working period</button>
    </div>
    <div class="col-12"><button class="btn btn-primary">Save</button></div>
</div></form>
@endsection
