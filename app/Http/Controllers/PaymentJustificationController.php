<?php

namespace App\Http\Controllers;

use App\Models\MeetingPaymentList;
use App\Models\PaymentJustification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentJustificationController extends Controller
{
    /**
     * Upload d'une pièce justificative
     */
    public function store(Request $request, MeetingPaymentList $paymentList)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'justification_type' => 'required|in:receipt,quittance,transfer_proof,bank_statement,mobile_money_proof,other',
            'description' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            
            // Générer un nom de fichier unique
            $uniqueFileName = 'justification_' . $paymentList->id . '_' . time() . '_' . Str::random(8) . '.' . $fileExtension;
            
            // Stocker le fichier
            $filePath = $file->storeAs('payment_justifications', $uniqueFileName, 'public');
            
            // Créer l'enregistrement en base
            $justification = PaymentJustification::create([
                'meeting_payment_list_id' => $paymentList->id,
                'uploaded_by' => $user->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $fileExtension,
                'file_size' => $fileSize,
                'justification_type' => $request->justification_type,
                'description' => $request->description,
                'reference_number' => $request->reference_number,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date
            ]);

            return response()->json([
                'message' => 'Pièce justificative uploadée avec succès',
                'justification' => $justification->load('uploader')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les pièces justificatives d'une liste de paiement
     */
    public function index(MeetingPaymentList $paymentList)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $justifications = $paymentList->justifications()
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'justifications' => $justifications
        ]);
    }

    /**
     * Supprimer une pièce justificative
     */
    public function destroy(PaymentJustification $justification)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier que l'utilisateur peut supprimer cette justification
        if ($justification->uploaded_by !== $user->id && !in_array('admin', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres justifications'], 403);
        }

        try {
            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($justification->file_path)) {
                Storage::disk('public')->delete($justification->file_path);
            }
            
            // Supprimer l'enregistrement en base
            $justification->delete();

            return response()->json([
                'message' => 'Pièce justificative supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger une pièce justificative
     */
    public function download(PaymentJustification $justification)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        if (!Storage::disk('public')->exists($justification->file_path)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        $path = Storage::disk('public')->path($justification->file_path);
        return response()->download($path, $justification->file_name);
    }

    /**
     * Mettre à jour une pièce justificative
     */
    public function update(Request $request, PaymentJustification $justification)
    {
        $user = Auth::user();
        
        if (!in_array('tresorier', $user->roles->pluck('name')->toArray()) && !in_array('Tresorier', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Vérifier que l'utilisateur peut modifier cette justification
        if ($justification->uploaded_by !== $user->id && !in_array('admin', $user->roles->pluck('name')->toArray())) {
            return response()->json(['message' => 'Vous ne pouvez modifier que vos propres justifications'], 403);
        }

        $validator = Validator::make($request->all(), [
            'justification_type' => 'sometimes|in:receipt,quittance,transfer_proof,bank_statement,mobile_money_proof,other',
            'description' => 'nullable|string|max:500',
            'reference_number' => 'nullable|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $justification->update($request->only([
                'justification_type',
                'description',
                'reference_number',
                'amount',
                'payment_date'
            ]));

            return response()->json([
                'message' => 'Pièce justificative mise à jour avec succès',
                'justification' => $justification->fresh()->load('uploader')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }
}
