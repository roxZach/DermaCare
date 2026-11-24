<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'details', 'ip_address'];

    public function user() { return $this->belongsTo(User::class); }

    /** Record an action for the audit trail. */
    public static function record(string $action, ?string $details = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()?->ip(),
        ]);
    }
}
