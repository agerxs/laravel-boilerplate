<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendeeController extends Controller
{
    /**
     * Formater les données d'un attendee
     */
    private function formatAttendee(MeetingAttendee $attendee)
    {
        // Recharger l'attendee avec les relations
        $attendee->load(['village', 'representative']);

        // Récupérer le village soit depuis la relation directe, soit depuis le représentant
        $village = $attendee->village ?? ($attendee->representative ? $attendee->representative->locality : null);
        
        return [
            'id' => $attendee->id,
            'name' => $attendee->name,
            'phone' => $attendee->phone,
            'role' => $attendee->role,
            'village' => [
                'id' => $village ? $village->id : null,
                'name' => $village ? $village->name : 'Village non trouvé'
            ],
            'is_expected' => $attendee->is_expected,
            'is_present' => $attendee->is_present,
            'attendance_status' => $attendee->attendance_status,
            'replacement_name' => $attendee->replacement_name,
            'replacement_phone' => $attendee->replacement_phone,
            'replacement_role' => $attendee->replacement_role,
            'arrival_time' => $attendee->arrival_time,
            'comments' => $attendee->comments,
            'payment_status' => $attendee->payment_status,
            'presence_photo' => $attendee->presence_photo,
            'presence_location' => $attendee->presence_location,
            'presence_timestamp' => $attendee->presence_timestamp
        ];
    }

    /**
     * Liste des participants d'une réunion
     */
    public function index(Meeting $meeting)
    {
        $attendees = $meeting->attendees()
            ->with(['village', 'representative'])
            ->get()
            ->map(function ($attendee) {
                return $this->formatAttendee($attendee);
            });

        return response()->json([
            'attendees' => $attendees
        ]);
    }

    /**
     * Marquer un participant comme présent
     */
    public function markPresent(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'arrival_time' => 'nullable|date',
        ]);

        $arrivalTime = $request->input('arrival_time') 
            ? Carbon::parse($request->input('arrival_time')) 
            : now();

        $attendee->markAsPresent($arrivalTime);

        return response()->json([
            'message' => 'Participant marqué comme présent',
            'attendee' => $this->formatAttendee($attendee)
        ]);
    }

    /**
     * Marquer un participant comme absent
     */
    public function markAbsent(MeetingAttendee $attendee)
    {
        $attendee->markAsAbsent();

        return response()->json([
            'message' => 'Participant marqué comme absent',
            'attendee' => $this->formatAttendee($attendee)
        ]);
    }

    /**
     * Enregistrer un remplaçant
     */
    public function setReplacement(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'replacement_name' => 'required|string|max:255',
            'replacement_phone' => 'nullable|string|max:20',
            'replacement_role' => 'nullable|string|max:255',
        ]);

        $attendee->setReplacement(
            $request->input('replacement_name'),
            $request->input('replacement_phone'),
            $request->input('replacement_role')
        );

        return response()->json([
            'message' => 'Remplaçant enregistré avec succès',
            'attendee' => $this->formatAttendee($attendee)
        ]);
    }

    /**
     * Ajouter un commentaire
     */
    public function addComment(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'comments' => 'required|string',
        ]);

        $attendee->update([
            'comments' => $request->input('comments')
        ]);

        return response()->json([
            'message' => 'Commentaire ajouté avec succès',
            'attendee' => $this->formatAttendee($attendee)
        ]);
    }

    /**
     * Confirmer la présence avec une photo
     */
    public function confirmPresenceWithPhoto(Request $request, MeetingAttendee $attendee)
    {
        $request->validate([
            'photo' => 'required|image|max:10240', // Max 10MB
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'required|date'
        ]);

        try {
            // Stocker la photo
            $photoPath = $request->file('photo')->store('presence-photos', 'public');

            // Enregistrer les informations de présence
            $attendee->update([
                'is_present' => true,
                'attendance_status' => 'present',
                'presence_photo' => $photoPath,
                'presence_location' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ],
                'presence_timestamp' => $request->timestamp,
                'arrival_time' => $request->timestamp
            ]);

            return response()->json([
                'message' => 'Présence confirmée avec succès',
                'attendee' => $this->formatAttendee($attendee)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la confirmation de présence',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer la photo de présence
     */
    public function deletePhoto(MeetingAttendee $attendee)
    {
        try {
            // Supprimer le fichier photo s'il existe
            if ($attendee->presence_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attendee->presence_photo);
            }

            // Mettre à jour l'attendee pour supprimer les informations de photo
            $attendee->update([
                'presence_photo' => null,
                'presence_location' => null,
                'presence_timestamp' => null
            ]);

            return response()->json([
                'message' => 'Photo supprimée avec succès',
                'attendee' => $this->formatAttendee($attendee)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 