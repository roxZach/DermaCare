<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeleRequest extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'lesion_id', 'subject', 'description', 'duration', 'status',
        'doctor_reply', 'visit_recommended', 'replied_at'];

    protected $casts = ['visit_recommended' => 'boolean', 'replied_at' => 'datetime'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function lesion() { return $this->belongsTo(Lesion::class); }
}
