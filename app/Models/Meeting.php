<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Meeting extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'title',
        'local_committee_id',
        'scheduled_date',
        'location',
        'description',
        'target_enrollments',
        'actual_enrollments',
        'agenda',
        'status',
        'reschedule_reason',
        'start_datetime',
        'end_datetime',
        'organizer_id',
        'prevalidated_at',
        'prevalidated_by',
        'validated_at',
        'validated_by',
        'validation_comments',
        'attendance_validated_at',
        'attendance_validated_by',
        'attendance_status',
        'attendance_submitted_at',
        'attendance_submitted_by',
        'created_by',
        'bulk_import_id',
        'parent_meeting_id',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'prevalidated_at' => 'datetime',
        'validated_at' => 'datetime',
        'attendance_validated_at' => 'datetime',
        'attendance_submitted_at' => 'datetime',
    ];

    public function localCommittee(): BelongsTo
    {
        return $this->belongsTo(LocalCommittee::class, 'local_committee_id');
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
        return $this->hasMany(AgendaItem::class)->orderBy('order');
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(MeetingMinutes::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Relation avec les participants à la réunion (attendees)
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function comments()
    {
        return $this->hasMany(MeetingComment::class)->orderBy('created_at', 'desc');
    }

    public function enrollmentRequests(): HasMany
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function paymentList(): HasOne
    {
        return $this->hasOne(MeetingPaymentList::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MeetingPayment::class);
    }

    /**
     * Relation avec les résultats des villages
     */
    public function villageResults(): HasMany
    {
        return $this->hasMany(VillageResult::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé la réunion
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'import par lots qui a créé cette réunion
     */
    public function bulkImport()
    {
        return $this->belongsTo(BulkImport::class);
    }

    /**
     * Relation avec l'utilisateur qui a prévalidé la réunion
     */
    public function prevalidator()
    {
        return $this->belongsTo(User::class, 'prevalidated_by');
    }

    /**
     * Relation avec l'utilisateur qui a validé la réunion
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation avec la réunion parent (pour les sous-réunions)
     */
    public function parentMeeting()
    {
        return $this->belongsTo(Meeting::class, 'parent_meeting_id');
    }

    /**
     * Relation avec les sous-réunions (enfants)
     */
    public function subMeetings()
    {
        return $this->hasMany(Meeting::class, 'parent_meeting_id');
    }

    /**
     * Vérifier si cette réunion est une réunion principale (parent)
     */
    public function isParentMeeting(): bool
    {
        return is_null($this->parent_meeting_id);
    }

    /**
     * Vérifier si cette réunion est une sous-réunion (enfant)
     */
    public function isSubMeeting(): bool
    {
        return !is_null($this->parent_meeting_id);
    }

    /**
     * Obtenir toutes les sous-réunions et leurs participants
     */
    public function getAllSubMeetingsWithAttendees()
    {
        return $this->subMeetings()->with(['attendees.locality', 'attendees.representative'])->get();
    }

    /**
     * Obtenir le nombre total de participants attendus dans toutes les sous-réunions
     */
    public function getTotalExpectedAttendees(): int
    {
        return $this->subMeetings()->with('attendees')->get()
            ->sum(function ($subMeeting) {
                return $subMeeting->attendees()->where('is_expected', true)->count();
            });
    }

    /**
     * Obtenir le nombre total de participants présents dans toutes les sous-réunions
     */
    public function getTotalPresentAttendees(): int
    {
        return $this->subMeetings()->with('attendees')->get()
            ->sum(function ($subMeeting) {
                return $subMeeting->attendees()->where('is_present', true)->count();
            });
    }

    /**
     * Vérifier si toutes les sous-réunions sont terminées
     */
    public function areAllSubMeetingsCompleted(): bool
    {
        return $this->subMeetings()->where('status', '!=', 'completed')->count() === 0;
    }

    /**
     * Vérifier si la réunion peut être éclatée en sous-réunions
     */
    public function canBeSplit(): bool
    {
        return $this->isParentMeeting() && 
               in_array($this->status, ['planned', 'scheduled']);
    }

    /**
     * Vérifier si la réunion est prévalidée
     */
    public function isPrevalidated(): bool
    {
        return $this->status === 'prevalidated';
    }

    /**
     * Vérifier si la réunion est validée définitivement
     */
    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function isAttendanceValidated(): bool
    {
        return $this->attendance_validated_at !== null;
    }

    public function attendanceValidator()
    {
        return $this->belongsTo(User::class, 'attendance_validated_by');
    }

    public function attendanceSubmitter()
    {
        return $this->belongsTo(User::class, 'attendance_submitted_by');
    }

    public function isAttendanceSubmitted(): bool
    {
        return $this->attendance_status === 'submitted';
    }

    public function isAttendanceDraft(): bool
    {
        return $this->attendance_status === 'draft';
    }

    /**
     * Vérifier si la réunion peut être modifiée
     */
    public function canBeEdited(): bool
    {
        return !in_array($this->status, ['prevalidated', 'validated']);
    }

    /**
     * Vérifier si la réunion peut être reprogrammée
     */
    public function canBeRescheduled(): bool
    {
        return !in_array($this->status, ['prevalidated', 'validated', 'cancelled', 'completed']);
    }

    /**
     * Vérifier si la réunion est en retard
     */
    public function isLate(): bool
    {
        return $this->status === 'planned' && $this->scheduled_date < now();
    }

    /**
     * Obtenir l'état lisible du statut
     */
    public function getStatusLabelAttribute(): string
    {
        // Si la réunion est en retard, on affiche "En retard" indépendamment du statut
        if ($this->isLate()) {
            return 'En retard';
        }

        return [
            'planned' => 'Planifiée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            'prevalidated' => 'Prévalidée',
            'validated' => 'Validée',
        ][$this->status] ?? 'Inconnu';
    }

    public static function rules()
    {
        return [
            'target_enrollments' => 'required|integer|min:0',
            'actual_enrollments' => 'required|integer|min:0|lte:target_enrollments',
        ];
    }
} 