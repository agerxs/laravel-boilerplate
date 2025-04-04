<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'localite_id',
        'representative_id',
        'name',
        'phone',
        'role',
        'is_expected',
        'is_present',
        'comments',
        'attendance_status',
        'replacement_name',
        'replacement_phone',
        'replacement_role',
        'arrival_time',
        'payment_status',
        'presence_photo',
        'presence_location',
        'presence_timestamp'
    ];

    protected $casts = [
        'is_expected' => 'boolean',
        'is_present' => 'boolean',
        'arrival_time' => 'datetime',
        'presence_timestamp' => 'datetime',
        'presence_location' => 'array'
    ];

    /**
     * Relation avec la réunion
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Relation avec la localité (village)
     */
    public function village()
    {
        return $this->belongsTo(Locality::class, 'localite_id');
    }

    /**
     * Relation avec le représentant original
     */
    public function representative()
    {
        return $this->belongsTo(Representative::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si le participant est présent
     */
    public function isPresent(): bool
    {
        return $this->is_present || $this->attendance_status === 'present' || $this->attendance_status === 'replaced';
    }

    /**
     * Vérifier si le participant a été remplacé
     */
    public function isReplaced(): bool
    {
        return $this->attendance_status === 'replaced' && !empty($this->replacement_name);
    }

    /**
     * Marquer le participant comme présent
     */
    public function markAsPresent($arrivalTime = null)
    {
        $this->is_present = true;
        $this->attendance_status = 'present';
        if ($arrivalTime) {
            $this->arrival_time = $arrivalTime;
        } else {
            $this->arrival_time = now();
        }
        $this->save();
        
        return $this;
    }

    /**
     * Marquer le participant comme absent
     */
    public function markAsAbsent()
    {
        $this->is_present = false;
        $this->attendance_status = 'absent';
        $this->save();
        
        return $this;
    }

    /**
     * Enregistrer un remplaçant pour ce participant
     */
    public function setReplacement($name, $phone = null, $role = null)
    {
        $this->attendance_status = 'replaced';
        $this->is_present = true;  // Le participant est considéré comme présent via son remplaçant
        $this->replacement_name = $name;
        $this->replacement_phone = $phone;
        $this->replacement_role = $role;
        $this->arrival_time = now();
        $this->save();
        
        return $this;
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(MeetingPaymentItem::class, 'attendee_id');
    }
} 