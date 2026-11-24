<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelVersion extends Model
{
    protected $fillable = ['version', 'architecture', 'file_name', 'description', 'metrics', 'is_active'];

    protected $casts = ['metrics' => 'array', 'is_active' => 'boolean'];
}
