<?php

namespace App\Services;

use App\Models\BulkImport;
use App\Models\Meeting;
use App\Models\LocalCommittee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BulkImportService
{
    /**
     * Traiter un import en lot
     */
    public function processImport(BulkImport $bulkImport)
    {
        try {
            DB::beginTransaction();

            $filePath = Storage::disk('public')->path($bulkImport->file_path);
            $data = $this->parseFile($filePath);
            
            $meetings = [];
            foreach ($data as $row) {
                $meeting = $this->createMeetingFromRow($row, $bulkImport->local_committee_id, $bulkImport->id);
                $meetings[] = $meeting;
            }

            $bulkImport->update([
                'status' => 'completed',
                'processed_at' => now(),
                'meetings_count' => count($meetings)
            ]);

            DB::commit();

            return [
                'success' => true,
                'meetings_created' => count($meetings),
                'meetings' => $meetings
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            $bulkImport->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Parser un fichier CSV/Excel
     */
    protected function parseFile($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            return $this->parseCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($filePath);
        }
        
        throw new \Exception('Format de fichier non supporté');
    }

    /**
     * Parser un fichier CSV
     */
    protected function parseCsv($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Impossible d\'ouvrir le fichier');
        }

        // Lire l'en-tête
        $headers = fgetcsv($handle);
        
        // Lire les données
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Parser un fichier Excel
     */
    protected function parseExcel($filePath)
    {
        // Utiliser une bibliothèque comme PhpSpreadsheet
        // Pour l'instant, on retourne un tableau vide
        return [];
    }

    /**
     * Créer une réunion à partir d'une ligne de données
     */
    protected function createMeetingFromRow($row, $localCommitteeId, $bulkImportId = null)
    {
        $scheduledDate = Carbon::createFromFormat('d/m/Y H:i', $row['date'] . ' ' . $row['heure']);
        
        return Meeting::create([
            'title' => $row['titre'],
            'description' => $row['description'] ?? null,
            'location' => $row['lieu'],
            'scheduled_date' => $scheduledDate,
            'local_committee_id' => $localCommitteeId,
            'status' => 'scheduled',
            'bulk_import_id' => $bulkImportId,
        ]);
    }

    /**
     * Générer un template CSV
     */
    public function generateTemplate($importType = 'meetings')
    {
        $headers = ['titre', 'date', 'heure', 'lieu', 'description'];
        $template = implode(',', $headers) . "\n";
        $template .= "Réunion mensuelle,15/01/2024,14:00,Salle de réunion,Description de la réunion\n";
        $template .= "Réunion trimestrielle,20/01/2024,15:30,Mairie,Ordre du jour\n";
        
        return $template;
    }
} 