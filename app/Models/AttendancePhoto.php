<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendee_id',
        'photo_url',
        'thumbnail_url',
        'original_size',
        'compressed_size',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'original_size' => 'integer',
        'compressed_size' => 'integer',
    ];

    public function attendee()
    {
        return $this->belongsTo(Attendee::class);
    }
} 