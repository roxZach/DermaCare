<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['prescription_id', 'medicine', 'dosage', 'frequency', 'duration', 'instructions'];
}
