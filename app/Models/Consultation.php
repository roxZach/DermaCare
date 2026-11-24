<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'appointment_id', 'complaint', 'findings', 'diagnosis', 'notes'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
}
