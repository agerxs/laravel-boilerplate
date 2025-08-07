<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceValidationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'token',
        'email',
        'expires_at',
        'used_at',
        'used_by'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isValid()
    {
        return !$this->used_at && $this->expires_at->isFuture();
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isUsed()
    {
        return !is_null($this->used_at);
    }

    public static function generateToken(Meeting $meeting, string $email)
    {
        // Supprimer les anciens tokens pour cette réunion et cet email
        self::where('meeting_id', $meeting->id)
            ->where('email', $email)
            ->delete();

        return self::create([
            'meeting_id' => $meeting->id,
            'token' => Str::random(64),
            'email' => $email,
            'expires_at' => now()->addDays(7), // Expire dans 7 jours
        ]);
    }
} 