<?php

namespace App\Http\Controllers;

use App\Models\MeetingPaymentList;
use App\Models\MeetingPaymentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MeetingPaymentExportController extends Controller
{
    /**
     * Exporte une liste de paiement individuelle
     */
    public function exportSingle(Request $request, MeetingPaymentList $paymentList)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier si la liste a déjà été exportée
        if ($paymentList->isExported()) {
            return response()->json([
                'message' => 'Cette liste a déjà été exportée',
                'exported_at' => $paymentList->exported_at,
                'export_reference' => $paymentList->export_reference
            ], 400);
        }

        // Générer une référence unique pour l'export
        $exportReference = 'EXP_' . Str::upper(Str::random(8)) . '_' . now()->format('Ymd_His');

        try {
            // Marquer la liste comme exportée
            $paymentList->markAsExported($exportReference, $user->id);

            // Marquer tous les éléments comme exportés
            foreach ($paymentList->paymentItems as $item) {
                $item->markAsExported($exportReference);
            }

            Log::info("Liste de paiement exportée", [
                'payment_list_id' => $paymentList->id,
                'meeting_id' => $paymentList->meeting_id,
                'export_reference' => $exportReference,
                'exported_by' => $user->id
            ]);

            return response()->json([
                'message' => 'Liste exportée avec succès',
                'export_reference' => $exportReference,
                'exported_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'export de la liste de paiement", [
                'payment_list_id' => $paymentList->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporte plusieurs listes de paiement sélectionnées
     */
    public function exportMultiple(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'payment_list_ids' => 'required|array',
            'payment_list_ids.*' => 'exists:meeting_payment_lists,id'
        ]);

        $paymentListIds = $request->payment_list_ids;
        $exportedLists = [];
        $errors = [];

        foreach ($paymentListIds as $listId) {
            $paymentList = MeetingPaymentList::find($listId);
            
            if (!$paymentList) {
                $errors[] = "Liste ID {$listId} non trouvée";
                continue;
            }

            if ($paymentList->isExported()) {
                $errors[] = "Liste ID {$listId} déjà exportée";
                continue;
            }

            try {
                $exportReference = 'EXP_' . Str::upper(Str::random(8)) . '_' . now()->format('Ymd_His');
                
                $paymentList->markAsExported($exportReference, $user->id);
                
                foreach ($paymentList->paymentItems as $item) {
                    $item->markAsExported($exportReference);
                }

                $exportedLists[] = [
                    'id' => $paymentList->id,
                    'meeting_title' => $paymentList->meeting->title,
                    'export_reference' => $exportReference
                ];

            } catch (\Exception $e) {
                $errors[] = "Erreur lors de l'export de la liste ID {$listId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => count($exportedLists) . ' listes exportées avec succès',
            'exported_lists' => $exportedLists,
            'errors' => $errors
        ]);
    }

    /**
     * Marque une liste de paiement comme payée
     */
    public function markAsPaid(Request $request, MeetingPaymentList $paymentList)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier que la liste a été exportée
        if (!$paymentList->isExported()) {
            return response()->json([
                'message' => 'Cette liste doit d\'abord être exportée avant d\'être marquée comme payée'
            ], 400);
        }

        // Vérifier que la liste n'a pas déjà été payée
        if ($paymentList->isPaid()) {
            return response()->json([
                'message' => 'Cette liste a déjà été marquée comme payée'
            ], 400);
        }

        // Vérifier qu'il y a au moins une pièce justificative
        if ($paymentList->justifications()->count() === 0) {
            return response()->json([
                'message' => 'Vous devez ajouter au moins une pièce justificative avant de marquer comme payé'
            ], 400);
        }

        try {
            $paymentList->markAsPaid($user->id);

            // Marquer tous les éléments comme payés
            foreach ($paymentList->paymentItems as $item) {
                $item->markAsPaid();
            }

            Log::info("Liste de paiement marquée comme payée", [
                'payment_list_id' => $paymentList->id,
                'meeting_id' => $paymentList->meeting_id,
                'paid_by' => $user->id,
                'justifications_count' => $paymentList->justifications()->count()
            ]);

            return response()->json([
                'message' => 'Liste marquée comme payée avec succès',
                'paid_at' => now()->toISOString(),
                'justifications_count' => $paymentList->justifications()->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du statut de paiement", [
                'payment_list_id' => $paymentList->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marque plusieurs listes de paiement comme payées
     */
    public function markMultipleAsPaid(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'payment_list_ids' => 'required|array',
            'payment_list_ids.*' => 'exists:meeting_payment_lists,id'
        ]);

        $paymentListIds = $request->payment_list_ids;
        $paidLists = [];
        $errors = [];

        foreach ($paymentListIds as $listId) {
            $paymentList = MeetingPaymentList::find($listId);
            
            if (!$paymentList) {
                $errors[] = "Liste ID {$listId} non trouvée";
                continue;
            }

            if (!$paymentList->isExported()) {
                $errors[] = "Liste ID {$listId} doit d'abord être exportée";
                continue;
            }

            if ($paymentList->isPaid()) {
                $errors[] = "Liste ID {$listId} déjà payée";
                continue;
            }

            try {
                $paymentList->markAsPaid($user->id);
                
                foreach ($paymentList->paymentItems as $item) {
                    $item->markAsPaid();
                }

                $paidLists[] = [
                    'id' => $paymentList->id,
                    'meeting_title' => $paymentList->meeting->title
                ];

            } catch (\Exception $e) {
                $errors[] = "Erreur lors de la mise à jour de la liste ID {$listId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => count($paidLists) . ' listes marquées comme payées avec succès',
            'paid_lists' => $paidLists,
            'errors' => $errors
        ]);
    }
}
