@extends('layouts.app')
@section('title', 'FAQ')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
<h3 class="page-title">{{ $faq->exists ? 'Edit FAQ' : 'New FAQ' }}</h3>
<form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="card"><div class="card-body row g-3">@csrf
    @if($faq->exists) @method('PUT') @endif
    <input type="hidden" name="from_log" value="{{ $fromLog }}">
    <div class="col-md-4"><label class="form-label">Category</label><input name="category" value="{{ old('category', $faq->category ?? 'General') }}" class="form-control" required list="fcats">
        <datalist id="fcats"><option value="Clinic"><option value="Appointments"><option value="Fees &amp; payment"><option value="Treatments"><option value="Skin care"><option value="Teledermatology"><option value="Account"></datalist></div>
    <div class="col-md-8"><label class="form-label">Question</label><input name="question" value="{{ old('question', $faq->question) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Other ways people ask it (one per line)</label>
        <textarea name="paraphrases" rows="4" class="form-control" placeholder="When are you open?&#10;What time does the clinic close?">{{ old('paraphrases', $faq->paraphrases) }}</textarea>
        <div class="form-text">Paraphrases improve matching accuracy. They are also used to evaluate the chatbot (top-1 / top-3 accuracy).</div></div>
    <div class="col-12"><label class="form-label">Answer</label><textarea name="answer" rows="4" class="form-control" required>{{ old('answer', $faq->answer) }}</textarea></div>
    <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $faq->is_active))><label class="form-check-label" for="ia">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary">Save and rebuild index</button></div>
</div></form></div></div>
@endsection
