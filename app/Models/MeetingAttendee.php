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
        'localite_id',
        'meeting_id',
        'attendance_status',
        'replacement_name',
        'replacement_phone',
        'replacement_role',
        'arrival_time',
        'payment_status',
        'presence_photo',
        'presence_location',
        'presence_timestamp',
    ];

    /**
     * Accesseur pour le nom (utilise le representative si disponible)
     */
    public function getNameAttribute($value)
    {
        // Si on a un représentant lié et qu'il a un nom
        if ($this->representative && $this->representative->name) {
            return $this->representative->name;
        }
        
        // Si on a un nom stocké directement dans l'attendee
        if ($value && !empty(trim($value))) {
            return $value;
        }
        
        // Si on a un représentant mais sans nom, essayer de construire le nom
        if ($this->representative) {
            $name = '';
            if ($this->representative->first_name) {
                $name .= $this->representative->first_name;
            }
            if ($this->representative->last_name) {
                $name .= ' ' . $this->representative->last_name;
            }
            if (!empty(trim($name))) {
                return trim($name);
            }
        }
        
        // Fallback : nom par défaut
        return 'Nom non défini';
    }

    /**
     * Accesseur pour le téléphone (utilise le representative si disponible)
     */
    public function getPhoneAttribute($value)
    {
        // Si on a un représentant lié et qu'il a un téléphone
        if ($this->representative && $this->representative->phone) {
            return $this->representative->phone;
        }
        
        // Si on a un téléphone stocké directement dans l'attendee
        if ($value && !empty(trim($value))) {
            return $value;
        }
        
        return null;
    }

    /**
     * Accesseur pour le rôle (utilise le representative si disponible)
     */
    public function getRoleAttribute($value)
    {
        // Si on a un représentant lié et qu'il a un rôle
        if ($this->representative && $this->representative->role) {
            return $this->representative->role;
        }
        
        // Si on a un rôle stocké directement dans l'attendee
        if ($value && !empty(trim($value))) {
            return $value;
        }
        
        return 'Rôle non défini';
    }

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
    public function locality()
    {
        return $this->belongsTo(Locality::class, 'localite_id');
    }

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

    /**
     * Méthode de débogage pour voir les données du participant
     */
    public function debugInfo()
    {
        $info = [
            'id' => $this->id,
            'meeting_id' => $this->meeting_id,
            'representative_id' => $this->representative_id,
            'localite_id' => $this->localite_id,
            'name_direct' => $this->getRawOriginal('name'),
            'name_computed' => $this->name,
            'phone_direct' => $this->getRawOriginal('phone'),
            'phone_computed' => $this->phone,
            'role_direct' => $this->getRawOriginal('role'),
            'role_computed' => $this->role,
        ];
        
        if ($this->representative) {
            $info['representative'] = [
                'id' => $this->representative->id,
                'name' => $this->representative->name,
                'first_name' => $this->representative->first_name ?? 'null',
                'last_name' => $this->representative->last_name ?? 'null',
                'phone' => $this->representative->phone,
                'role' => $this->representative->role,
            ];
        } else {
            $info['representative'] = 'null';
        }
        
        return $info;
    }
} 