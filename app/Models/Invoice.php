<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['number', 'patient_id', 'appointment_id', 'subtotal', 'discount', 'discount_reason', 'total',
        'status', 'created_by'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public static function nextNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . str_pad((string) (static::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }
}
