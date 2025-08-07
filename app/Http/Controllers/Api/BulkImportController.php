<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BulkImport;
use App\Models\LocalCommittee;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Utils\Constants;
use App\Services\BulkImportService;

class BulkImportController extends Controller
{
    protected $bulkImportService;

    public function __construct(BulkImportService $bulkImportService)
    {
        $this->bulkImportService = $bulkImportService;
    }

    /**
     * Liste des imports en lot
     */
    public function index(Request $request)
    {
        $query = BulkImport::with(['localCommittee', 'user'])
            ->when($request->import_type, function ($q, $type) {
                return $q->where('import_type', $type);
            })
            ->when($request->date_from, function ($q, $date) {
                return $q->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($q, $date) {
                return $q->whereDate('created_at', '<=', $date);
            })
            ->orderBy('created_at', 'desc');

        $imports = $query->paginate($request->per_page ?? 10);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des imports récupérée avec succès', [
            'imports' => $imports,
            'meta' => [
                'current_page' => $imports->currentPage(),
                'last_page' => $imports->lastPage(),
                'per_page' => $imports->perPage(),
                'total' => $imports->total()
            ]
        ]);
    }

    /**
     * Détails d'un import en lot
     */
    public function show(BulkImport $bulkImport)
    {
        $bulkImport->load(['localCommittee', 'user', 'meetings']);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Import récupéré avec succès', [
            'import' => $bulkImport
        ]);
    }

    /**
     * Créer un nouvel import en lot
     */
    public function store(Request $request)
    {
        $request->validate([
            'local_committee_id' => 'required|exists:local_committees,id',
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'import_type' => 'required|in:meetings',
        ]);

        try {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('bulk-imports', $fileName, 'public');

            $bulkImport = BulkImport::create([
                'user_id' => Auth::id(),
                'local_committee_id' => $request->local_committee_id,
                'import_type' => $request->import_type,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            return $this->format(Constants::JSON_STATUS_SUCCESS, 201, 'Import créé avec succès', [
                'import' => $bulkImport
            ]);
        } catch (\Exception $e) {
            return $this->format(Constants::JSON_STATUS_ERROR, 500, 'Erreur lors de la création de l\'import: ' . $e->getMessage());
        }
    }

    /**
     * Traiter un import en lot
     */
    public function process(BulkImport $bulkImport)
    {
        try {
            $result = $this->bulkImportService->processImport($bulkImport);
            
            return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Import traité avec succès', [
                'import' => $bulkImport->fresh(),
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return $this->format(Constants::JSON_STATUS_ERROR, 500, 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    /**
     * Télécharger le fichier d'import
     */
    public function download(BulkImport $bulkImport)
    {
        if (!Storage::disk('public')->exists($bulkImport->file_path)) {
            return $this->format(Constants::JSON_STATUS_ERROR, 404, 'Fichier non trouvé');
        }

        return Storage::disk('public')->download($bulkImport->file_path, $bulkImport->file_name);
    }

    /**
     * Générer un template d'import
     */
    public function generateTemplate(Request $request)
    {
        $request->validate([
            'import_type' => 'required|in:meetings',
        ]);

        try {
            $template = $this->bulkImportService->generateTemplate($request->import_type);
            
            return response($template)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="template_import.csv"');
        } catch (\Exception $e) {
            return $this->format(Constants::JSON_STATUS_ERROR, 500, 'Erreur lors de la génération du template: ' . $e->getMessage());
        }
    }
} 