<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'date_of_birth', 'gender', 'address', 'allergies',
        'medical_history', 'fitzpatrick_type', 'sun_exposure', 'family_history_skin_cancer', 'personal_history_skin_cancer'];

    protected $casts = [
        'date_of_birth' => 'date',
        'family_history_skin_cancer' => 'boolean',
        'personal_history_skin_cancer' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function consultations() { return $this->hasMany(Consultation::class); }
    public function lesions() { return $this->hasMany(Lesion::class); }
    public function prescriptions() { return $this->hasMany(Prescription::class); }
    public function biopsyOrders() { return $this->hasMany(BiopsyOrder::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function teleRequests() { return $this->hasMany(TeleRequest::class); }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getCodeAttribute(): string
    {
        return 'P-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
