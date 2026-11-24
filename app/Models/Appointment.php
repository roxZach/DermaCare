<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'date', 'time', 'status', 'reason', 'queue_no', 'booked_by', 'reminder_sent_at'];

    protected $casts = ['date' => 'date', 'reminder_sent_at' => 'datetime'];

    public const STATUSES = ['booked', 'checked_in', 'completed', 'cancelled'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function consultation() { return $this->hasOne(Consultation::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function review() { return $this->hasOne(DoctorReview::class); }

    public function getStartsAtAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->date->toDateString() . ' ' . $this->time);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'booked' && $this->starts_at->isFuture();
    }
}
