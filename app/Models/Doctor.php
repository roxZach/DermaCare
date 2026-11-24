<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = ['user_id', 'specialty', 'qualifications', 'experience_years', 'consultation_fee', 'bio'];

    public function user() { return $this->belongsTo(User::class); }
    public function schedules() { return $this->hasMany(DoctorSchedule::class)->orderBy('day_of_week'); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function reviews() { return $this->hasMany(DoctorReview::class); }

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? 'Unknown';
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    /**
     * Free time slots for a given date, based on the weekly schedule minus booked appointments.
     */
    public function availableSlots(string $date): array
    {
        $day = \Carbon\Carbon::parse($date);
        if ($day->isPast() && ! $day->isToday()) {
            return [];
        }
        $taken = $this->appointments()->whereDate('date', $day)->where('status', '!=', 'cancelled')
            ->pluck('time')->map(fn ($t) => substr($t, 0, 5))->all();
        $slots = [];
        foreach ($this->schedules->where('day_of_week', $day->dayOfWeek) as $s) {
            $t = \Carbon\Carbon::parse($day->toDateString() . ' ' . $s->start_time);
            $end = \Carbon\Carbon::parse($day->toDateString() . ' ' . $s->end_time);
            while ($t->copy()->addMinutes($s->slot_minutes)->lte($end)) {
                $hm = $t->format('H:i');
                if (! in_array($hm, $taken) && $t->isFuture()) {
                    $slots[] = $hm;
                }
                $t->addMinutes($s->slot_minutes);
            }
        }
        return $slots;
    }
}
