<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MeetingsImport implements ToArray, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    public $errors = [];
    public $data = [];

    public function array(array $array)
    {
        foreach ($array as $row) {
            // Valider et nettoyer les données
            $cleanedRow = $this->cleanRow($row);
            
            if ($cleanedRow) {
                $this->data[] = $cleanedRow;
            }
        }
    }

    private function cleanRow($row)
    {
        // Vérifier que les colonnes requises existent
        $requiredColumns = ['titre', 'date', 'heure', 'lieu'];
        
        foreach ($requiredColumns as $column) {
            if (!isset($row[$column]) || empty(trim($row[$column]))) {
                $this->errors[] = "Colonne '$column' manquante ou vide dans la ligne";
                return null;
            }
        }

        // Nettoyer et valider la date
        $date = $this->parseDate($row['date']);
        if (!$date) {
            $this->errors[] = "Date invalide: {$row['date']}";
            return null;
        }

        // Nettoyer et valider l'heure
        $time = $this->parseTime($row['heure']);
        if (!$time) {
            $this->errors[] = "Heure invalide: {$row['heure']}";
            return null;
        }

        return [
            'title' => trim($row['titre']),
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'location' => trim($row['lieu'])
        ];
    }

    private function parseDate($dateString)
    {
        $dateString = trim($dateString);
        
        // Formats possibles
        $formats = [
            'Y-m-d',     // 2024-01-15
            'd/m/Y',     // 15/01/2024
            'd-m-Y',     // 15-01-2024
            'd.m.Y',     // 15.01.2024
            'Y/m/d',     // 2024/01/15
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function parseTime($timeString)
    {
        $timeString = trim($timeString);
        
        // Formats possibles
        $formats = [
            'H:i',       // 14:30
            'H:i:s',     // 14:30:00
            'g:i A',     // 2:30 PM
            'G:i',       // 14:30 (24h)
        ];

        foreach ($formats as $format) {
            $time = \DateTime::createFromFormat($format, $timeString);
            if ($time !== false) {
                return $time->format('H:i');
            }
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'titre' => 'required|string|max:255',
            'date' => 'required',
            'heure' => 'required',
            'lieu' => 'required|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'titre.required' => 'Le titre est obligatoire',
            'date.required' => 'La date est obligatoire',
            'heure.required' => 'L\'heure est obligatoire',
            'lieu.required' => 'Le lieu est obligatoire',
        ];
    }



    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
