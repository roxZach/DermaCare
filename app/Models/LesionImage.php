<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LesionImage extends Model
{
    protected $fillable = ['lesion_id', 'path', 'source', 'image_type', 'uploaded_by', 'quality_ok', 'quality_message'];

    protected $casts = ['quality_ok' => 'boolean'];

    public function lesion() { return $this->belongsTo(Lesion::class); }
    public function analyses() { return $this->hasMany(LesionAnalysis::class)->latest(); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
