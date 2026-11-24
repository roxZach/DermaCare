<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesion extends Model
{
    protected $fillable = ['patient_id', 'label', 'body_region', 'view', 'x', 'y', 'notes', 'created_by'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function images() { return $this->hasMany(LesionImage::class)->latest(); }
    public function biopsyOrders() { return $this->hasMany(BiopsyOrder::class); }

    /** Latest dermatologist-verified analysis, if any. */
    public function latestVerifiedAnalysis()
    {
        return LesionAnalysis::whereIn('lesion_image_id', $this->images()->pluck('id'))
            ->whereIn('status', ['confirmed', 'overridden'])->latest()->first();
    }
}
