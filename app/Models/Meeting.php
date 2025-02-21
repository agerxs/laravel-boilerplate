<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'local_committee_id',
        'scheduled_date',
        'status',
        'people_to_enroll_count'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime'
    ];

    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function agenda(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(MeetingMinutes::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function comments()
    {
        return $this->hasMany(MeetingComment::class);
    }

    public function localCommittees(): BelongsToMany
    {
        return $this->belongsToMany(LocalCommittee::class, 'meeting_local_committees');
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }
} 