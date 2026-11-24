<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $fillable = ['doctor_id', 'day_of_week', 'start_time', 'end_time', 'slot_minutes'];

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function doctor() { return $this->belongsTo(Doctor::class); }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '?';
    }
}
