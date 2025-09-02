<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\VillageResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class VillageResultController extends Controller
{
    /**
     * Créer un résultat de village pour une réunion
     */
    public function store(Request $request, $meetingId): JsonResponse
    {
        try {
            // Vérifier que la réunion existe
            $meeting = Meeting::findOrFail($meetingId);
            
            // Validation des données
            $validator = Validator::make($request->all(), [
                'meetingId' => 'required|integer|exists:meetings,id',
                'localityId' => 'required|integer|exists:localite,id',
                'peopleToEnrollCount' => 'nullable|integer|min:0',
                'peopleEnrolledCount' => 'nullable|integer|min:0',
                'cmuCardsAvailableCount' => 'nullable|integer|min:0',
                'cmuCardsDistributedCount' => 'nullable|integer|min:0',
                'complaintsReceivedCount' => 'nullable|integer|min:0',
                'complaintsProcessedCount' => 'nullable|integer|min:0',
                'comments' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:draft,submitted,validated',
            ]);

           

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Vérifier si un résultat existe déjà pour cette réunion et cette localité
            $existingResult = VillageResult::where('meeting_id', $request->meetingId)
                ->where('localite_id', $request->localityId)
                ->first();

            if ($existingResult) {
                // Mettre à jour le résultat existant
                $existingResult->update([
                    'people_to_enroll_count' => $request->peopleToEnrollCount,
                    'people_enrolled_count' => $request->peopleEnrolledCount,
                    'cmu_cards_available_count' => $request->cmuCardsAvailableCount,
                    'cmu_cards_distributed_count' => $request->cmuCardsDistributedCount,
                    'complaints_received_count' => $request->complaintsReceivedCount,
                    'complaints_processed_count' => $request->complaintsProcessedCount,
                    'comments' => $request->comments,
                    'status' => $request->status ?? 'submitted',
                    'submitted_by' => Auth::id(),
                    'submitted_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Résultat de village mis à jour avec succès',
                    'data' => $existingResult->fresh()
                ], 200);
            }

            // Créer un nouveau résultat
            $villageResult = VillageResult::create([
                'meeting_id' => $request->meetingId,
                'localite_id' => $request->localityId,
                'people_to_enroll_count' => $request->people_to_enroll_count,
                'people_enrolled_count' => $request->people_enrolled_count,
                'cmu_cards_available_count' => $request->cmu_cards_available_count,
                'cmu_cards_distributed_count' => $request->cmu_cards_distributed_count,
                'complaints_received_count' => $request->complaints_received_count,
                'complaints_processed_count' => $request->complaints_processed_count,
                'comments' => $request->comments,
                'status' => $request->status ?? 'submitted',
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Résultat de village créé avec succès',
                'data' => $villageResult
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du résultat de village',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un résultat de village
     */
    public function update(Request $request, $meetingId, $villageResultId): JsonResponse
    {
        try {
            // Vérifier que la réunion et le résultat existent
            $meeting = Meeting::findOrFail($meetingId);
            $villageResult = VillageResult::where('meeting_id', $meetingId)
                ->where('id', $villageResultId)
                ->firstOrFail();

            // Validation des données
            $validator = Validator::make($request->all(), [
                'people_to_enroll_count' => 'nullable|integer|min:0',
                'people_enrolled_count' => 'nullable|integer|min:0',
                'cmu_cards_available_count' => 'nullable|integer|min:0',
                'cmu_cards_distributed_count' => 'nullable|integer|min:0',
                'complaints_received_count' => 'nullable|integer|min:0',
                'complaints_processed_count' => 'nullable|integer|min:0',
                'comments' => 'nullable|string|max:1000',
                'status' => 'nullable|string|in:draft,submitted,validated',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Mettre à jour le résultat
            $villageResult->update([
                'people_to_enroll_count' => $request->people_to_enroll_count,
                'people_enrolled_count' => $request->people_enrolled_count,
                'cmu_cards_available_count' => $request->cmu_cards_available_count,
                'cmu_cards_distributed_count' => $request->cmu_cards_distributed_count,
                'complaints_received_count' => $request->complaints_received_count,
                'complaints_processed_count' => $request->complaints_processed_count,
                'comments' => $request->comments,
                'status' => $request->status ?? $villageResult->status,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Résultat de village mis à jour avec succès',
                'data' => $villageResult->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du résultat de village',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir tous les résultats de villages d'une réunion
     */
    public function index($meetingId): JsonResponse
    {
        try {
            // Vérifier que la réunion existe
            $meeting = Meeting::findOrFail($meetingId);
            
            // Récupérer tous les résultats de villages pour cette réunion
            $villageResults = VillageResult::where('meeting_id', $meetingId)
                ->with(['village', 'submitter', 'validator'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $villageResults
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des résultats de villages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un résultat de village spécifique
     */
    public function show($meetingId, $villageResultId): JsonResponse
    {
        try {
            // Vérifier que la réunion et le résultat existent
            $meeting = Meeting::findOrFail($meetingId);
            $villageResult = VillageResult::where('meeting_id', $meetingId)
                ->where('id', $villageResultId)
                ->with(['village', 'submitter', 'validator'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $villageResult
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résultat de village',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un résultat de village
     */
    public function destroy($meetingId, $villageResultId): JsonResponse
    {
        try {
            // Vérifier que la réunion et le résultat existent
            $meeting = Meeting::findOrFail($meetingId);
            $villageResult = VillageResult::where('meeting_id', $meetingId)
                ->where('id', $villageResultId)
                ->firstOrFail();

            // Supprimer le résultat
            $villageResult->delete();

            return response()->json([
                'success' => true,
                'message' => 'Résultat de village supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du résultat de village',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
