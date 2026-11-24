<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'link', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    /** Create an in-app notification for a user. */
    public static function send(?int $userId, string $title, ?string $message = null, ?string $link = null): void
    {
        if ($userId) {
            static::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'link' => $link]);
        }
    }
}
