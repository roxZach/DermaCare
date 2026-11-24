<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'consultation_id', 'advice', 'follow_up_date'];

    protected $casts = ['follow_up_date' => 'date'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function items() { return $this->hasMany(PrescriptionItem::class); }
}
