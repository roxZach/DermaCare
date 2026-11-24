<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiopsyOrder extends Model
{
    protected $fillable = ['patient_id', 'doctor_id', 'lesion_id', 'test_type', 'site', 'clinical_note', 'status',
        'findings', 'report_path', 'histo_image_path', 'lab_user_id', 'completed_at', 'reviewed_at', 'doctor_comment'];

    protected $casts = ['completed_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public const LAB_STATUSES = ['ordered', 'sample_collected', 'sent_to_lab', 'in_process'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function lesion() { return $this->belongsTo(Lesion::class); }
    public function labUser() { return $this->belongsTo(User::class, 'lab_user_id'); }
}
