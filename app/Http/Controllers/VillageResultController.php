<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\VillageResult;
use App\Models\Locality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class VillageResultController extends Controller
{
    /**
     * Afficher la page des résultats des villages pour une réunion
     */
    public function index(Meeting $meeting)
    {
        // Vérifier si c'est une requête API
        if (request()->expectsJson()) {
            return $this->indexApi($meeting);
        }

        // Récupérer tous les villages participants à cette réunion
        $villages = $meeting->attendees()
            ->with('village')
            ->get()
            ->pluck('village')
            ->filter() // Filtrer les valeurs null
            ->unique('id')
            ->values();

        // Récupérer les résultats existants
        $results = VillageResult::where('meeting_id', $meeting->id)
            ->with(['village', 'submitter', 'validator'])
            ->get();

        // Calculer les totaux
        $totals = [
            'people_to_enroll_count' => $results->sum('people_to_enroll_count'),
            'people_enrolled_count' => $results->sum('people_enrolled_count'),
            'cmu_cards_available_count' => $results->sum('cmu_cards_available_count'),
            'cmu_cards_distributed_count' => $results->sum('cmu_cards_distributed_count'),
            'complaints_received_count' => $results->sum('complaints_received_count'),
            'complaints_processed_count' => $results->sum('complaints_processed_count'),
        ];

        // Calculer les taux globaux
        $globalRates = [
            'enrollment_rate' => $totals['people_to_enroll_count'] > 0 
                ? round(($totals['people_enrolled_count'] / $totals['people_to_enroll_count']) * 100, 2) 
                : 0,
            'cmu_distribution_rate' => $totals['cmu_cards_available_count'] > 0 
                ? round(($totals['cmu_cards_distributed_count'] / $totals['cmu_cards_available_count']) * 100, 2) 
                : 0,
            'complaint_processing_rate' => $totals['complaints_received_count'] > 0 
                ? round(($totals['complaints_processed_count'] / $totals['complaints_received_count']) * 100, 2) 
                : 0,
        ];

        $summary = [
            'total_villages' => $villages->count(),
            'submitted_villages' => $results->where('status', 'submitted')->count(),
            'validated_villages' => $results->where('status', 'validated')->count(),
            'draft_villages' => $results->where('status', 'draft')->count(),
        ];

        // Debug: Afficher les résultats avec les propriétés calculées
        $debugResults = $results->map(function($result) {
            return [
                'id' => $result->id,
                'localite_id' => $result->localite_id,
                'enrollment_rate' => $result->enrollment_rate,
                'cmu_distribution_rate' => $result->cmu_distribution_rate,
                'complaint_processing_rate' => $result->complaint_processing_rate,
                'people_to_enroll_count' => $result->people_to_enroll_count,
                'people_enrolled_count' => $result->people_enrolled_count,
            ];
        });
        
        Log::info('Résultats avec propriétés calculées:', $debugResults->toArray());
        
        return Inertia::render('Meetings/VillageResults', [
            'meeting' => $meeting,
            'villages' => $villages,
            'results' => $results,
            'totals' => $totals,
            'globalRates' => $globalRates,
            'summary' => $summary,
            'canValidate' => in_array(Auth::user()->role, ['sous-prefet', 'Sous-prefet', 'admin', 'Admin']),
        ]);
    }

    /**
     * Afficher les résultats d'un village spécifique
     */
    public function show(Meeting $meeting, Locality $village)
    {
        $result = VillageResult::where('meeting_id', $meeting->id)
            ->where('localite_id', $village->id)
            ->with(['village', 'submitter', 'validator'])
            ->first();

        return Inertia::render('Meetings/VillageResultDetail', [
            'meeting' => $meeting,
            'village' => $village,
            'result' => $result,
            'canValidate' => in_array(Auth::user()->role, ['sous-prefet', 'Sous-prefet', 'admin', 'Admin']),
        ]);
    }

    /**
     * Créer ou mettre à jour les résultats d'un village
     */
    public function store(Request $request, Meeting $meeting, Locality $village)
    {
        $validator = Validator::make($request->all(), VillageResult::rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validation personnalisée
        $errors = [];
        $peopleToEnroll = $request->input('people_to_enroll_count');
        $peopleEnrolled = $request->input('people_enrolled_count');
        $cmuAvailable = $request->input('cmu_cards_available_count');
        $cmuDistributed = $request->input('cmu_cards_distributed_count');
        $complaintsReceived = $request->input('complaints_received_count');
        $complaintsProcessed = $request->input('complaints_processed_count');

        if ($peopleEnrolled > $peopleToEnroll && $peopleToEnroll > 0) {
            $errors[] = 'Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler';
        }

        if ($cmuDistributed > $cmuAvailable && $cmuAvailable > 0) {
            $errors[] = 'Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles';
        }

        if ($complaintsProcessed > $complaintsReceived && $complaintsReceived > 0) {
            $errors[] = 'Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues';
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ], 422);
        }

        $result = VillageResult::updateOrCreate(
            [
                'meeting_id' => $meeting->id,
                'localite_id' => $village->id
            ],
            [
                'people_to_enroll_count' => $peopleToEnroll,
                'people_enrolled_count' => $peopleEnrolled,
                'cmu_cards_available_count' => $cmuAvailable,
                'cmu_cards_distributed_count' => $cmuDistributed,
                'complaints_received_count' => $complaintsReceived,
                'complaints_processed_count' => $complaintsProcessed,
                'comments' => $request->input('comments'),
                'status' => $request->input('status', 'draft'),
                'submitted_by' => Auth::id(),
                'submitted_at' => $request->input('status') === 'submitted' ? now() : null,
            ]
        );

        return response()->json([
            'message' => 'Résultats sauvegardés avec succès',
            'result' => $result->load(['village', 'submitter'])
        ]);
    }

    /**
     * Soumettre les résultats d'un village
     */
    public function submit(Request $request, Meeting $meeting, Locality $village)
    {
        $result = VillageResult::where('meeting_id', $meeting->id)
            ->where('localite_id', $village->id)
            ->first();

        if (!$result) {
            return response()->json([
                'message' => 'Aucun résultat trouvé pour ce village'
            ], 404);
        }

        $result->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Résultats soumis avec succès',
            'result' => $result->load(['village', 'submitter'])
        ]);
    }

    /**
     * Valider les résultats d'un village (pour les superviseurs)
     */
    public function validateResults(Request $request, Meeting $meeting, Locality $village)
    {
        $result = VillageResult::where('meeting_id', $meeting->id)
            ->where('localite_id', $village->id)
            ->first();

        if (!$result) {
            return response()->json([
                'message' => 'Aucun résultat trouvé pour ce village'
            ], 404);
        }

        $result->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => Auth::id(),
            'validation_comments' => $request->input('validation_comments'),
        ]);

        return response()->json([
            'message' => 'Résultats validés avec succès',
            'result' => $result->load(['village', 'submitter', 'validator'])
        ]);
    }

    /**
     * Obtenir tous les résultats d'une réunion (API)
     */
    public function indexApi(Meeting $meeting)
    {
        $results = VillageResult::where('meeting_id', $meeting->id)
            ->with(['village', 'submitter', 'validator'])
            ->get();

        // Calculer les totaux
        $totals = [
            'people_to_enroll_count' => $results->sum('people_to_enroll_count'),
            'people_enrolled_count' => $results->sum('people_enrolled_count'),
            'cmu_cards_available_count' => $results->sum('cmu_cards_available_count'),
            'cmu_cards_distributed_count' => $results->sum('cmu_cards_distributed_count'),
            'complaints_received_count' => $results->sum('complaints_received_count'),
            'complaints_processed_count' => $results->sum('complaints_processed_count'),
        ];

        // Calculer les taux globaux
        $globalRates = [
            'enrollment_rate' => $totals['people_to_enroll_count'] > 0 
                ? round(($totals['people_enrolled_count'] / $totals['people_to_enroll_count']) * 100, 2) 
                : 0,
            'cmu_distribution_rate' => $totals['cmu_cards_available_count'] > 0 
                ? round(($totals['cmu_cards_distributed_count'] / $totals['cmu_cards_available_count']) * 100, 2) 
                : 0,
            'complaint_processing_rate' => $totals['complaints_received_count'] > 0 
                ? round(($totals['complaints_processed_count'] / $totals['complaints_received_count']) * 100, 2) 
                : 0,
        ];

        return response()->json([
            'results' => $results,
            'totals' => $totals,
            'global_rates' => $globalRates,
            'summary' => [
                'total_villages' => $results->count(),
                'submitted_villages' => $results->where('status', 'submitted')->count(),
                'validated_villages' => $results->where('status', 'validated')->count(),
                'draft_villages' => $results->where('status', 'draft')->count(),
            ]
        ]);
    }

    /**
     * Supprimer les résultats d'un village
     */
    public function destroy(Meeting $meeting, Locality $village)
    {
        $result = VillageResult::where('meeting_id', $meeting->id)
            ->where('localite_id', $village->id)
            ->first();

        if (!$result) {
            return response()->json([
                'message' => 'Aucun résultat trouvé pour ce village'
            ], 404);
        }

        $result->delete();

        return response()->json([
            'message' => 'Résultats supprimés avec succès'
        ]);
    }
}
