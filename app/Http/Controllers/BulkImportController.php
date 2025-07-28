<?php

namespace App\Http\Controllers;

use App\Models\BulkImport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BulkImportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = BulkImport::with(['user', 'localCommittee']);

        // Filtrer selon les rôles de l'utilisateur
        if ($user->hasRole(['prefet', 'Prefet'])) {
            $query->whereHas('localCommittee.locality', function ($q) use ($user) {
                $q->where('id', $user->locality_id)
                  ->orWhere('parent_id', $user->locality_id);
            });
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            $query->whereHas('localCommittee', function ($q) use ($user) {
                $q->where('locality_id', $user->locality_id);
            });
        }

        // Filtres
        if ($request->filled('import_type')) {
            $query->where('import_type', $request->import_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $imports = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('BulkImports/Index', [
            'imports' => $imports,
            'filters' => $request->only(['import_type', 'date_from', 'date_to']),
        ]);
    }

    public function show(BulkImport $bulkImport)
    {
        // Vérifier les permissions
        $user = auth()->user();
        
        if ($user->hasRole(['prefet', 'Prefet'])) {
            $allowed = $bulkImport->localCommittee->locality->id === $user->locality_id ||
                      $bulkImport->localCommittee->locality->parent_id === $user->locality_id;
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            $allowed = $bulkImport->localCommittee->locality_id === $user->locality_id;
        } else {
            $allowed = $bulkImport->user_id === $user->id;
        }

        if (!$allowed) {
            abort(403, 'Accès non autorisé');
        }

        $bulkImport->load(['user', 'localCommittee', 'meetings']);

        return Inertia::render('BulkImports/Show', [
            'import' => $bulkImport,
        ]);
    }

    public function download(BulkImport $bulkImport)
    {
        // Vérifier les permissions
        $user = auth()->user();
        
        if ($user->hasRole(['prefet', 'Prefet'])) {
            $allowed = $bulkImport->localCommittee->locality->id === $user->locality_id ||
                      $bulkImport->localCommittee->locality->parent_id === $user->locality_id;
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            $allowed = $bulkImport->localCommittee->locality_id === $user->locality_id;
        } else {
            $allowed = $bulkImport->user_id === $user->id;
        }

        if (!$allowed) {
            abort(403, 'Accès non autorisé');
        }

        if (!$bulkImport->file_path || !file_exists(storage_path('app/public/' . $bulkImport->file_path))) {
            abort(404, 'Fichier non trouvé');
        }

        return response()->download(
            storage_path('app/public/' . $bulkImport->file_path),
            $bulkImport->original_filename
        );
    }

    public function destroy(BulkImport $bulkImport)
    {
        // Vérifier les permissions
        $user = auth()->user();
        
        if ($user->hasRole(['prefet', 'Prefet'])) {
            $allowed = $bulkImport->localCommittee->locality->id === $user->locality_id ||
                      $bulkImport->localCommittee->locality->parent_id === $user->locality_id;
        } elseif ($user->hasRole(['sous-prefet', 'Sous-prefet', 'secretaire', 'Secrétaire'])) {
            $allowed = $bulkImport->localCommittee->locality_id === $user->locality_id;
        } else {
            $allowed = $bulkImport->user_id === $user->id;
        }

        if (!$allowed) {
            abort(403, 'Accès non autorisé');
        }

        // Supprimer le fichier physique
        if ($bulkImport->file_path && file_exists(storage_path('app/public/' . $bulkImport->file_path))) {
            unlink(storage_path('app/public/' . $bulkImport->file_path));
        }

        $bulkImport->delete();

        return redirect()->route('bulk-imports.index')
            ->with('success', 'Import supprimé avec succès');
    }
}
