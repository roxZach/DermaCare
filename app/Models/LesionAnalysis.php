<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LesionAnalysis extends Model
{
    protected $fillable = ['lesion_image_id', 'requested_by', 'model_version', 'predicted_class', 'confidence',
        'probabilities', 'risk_level', 'heatmap_path', 'status', 'final_diagnosis', 'doctor_note', 'reviewed_by',
        'reviewed_at', 'error'];

    protected $casts = ['probabilities' => 'array', 'reviewed_at' => 'datetime'];

    /** The seven HAM10000 diagnostic classes. */
    public const CLASSES = [
        'akiec' => 'Actinic keratosis / intraepithelial carcinoma',
        'bcc' => 'Basal cell carcinoma',
        'bkl' => 'Benign keratosis',
        'df' => 'Dermatofibroma',
        'mel' => 'Melanoma',
        'nv' => 'Melanocytic nevus (mole)',
        'vasc' => 'Vascular lesion',
    ];

    public function image() { return $this->belongsTo(LesionImage::class, 'lesion_image_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public static function className(?string $code): string
    {
        return self::CLASSES[$code] ?? ($code ?? '-');
    }

    public function isVerified(): bool
    {
        return in_array($this->status, ['confirmed', 'overridden']);
    }

    /** Probabilities sorted from highest to lowest. */
    public function sortedProbabilities(): array
    {
        $p = $this->probabilities ?? [];
        arsort($p);
        return $p;
    }
}
