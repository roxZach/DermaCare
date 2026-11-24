<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    protected $fillable = ['user_id', 'session_id', 'question', 'answer', 'faq_id', 'confidence', 'engine', 'escalated',
        'suggest_booking', 'rating', 'resolved'];

    protected $casts = ['escalated' => 'boolean', 'suggest_booking' => 'boolean', 'resolved' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function faq() { return $this->belongsTo(Faq::class); }
}
