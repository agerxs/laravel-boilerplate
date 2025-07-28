<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateController extends Controller
{
    public function downloadMeetingsTemplate()
    {
        $data = [
            [
                'titre' => 'Réunion mensuelle - Janvier 2024',
                'date' => '2024-01-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ],
            [
                'titre' => 'Réunion mensuelle - Février 2024',
                'date' => '2024-02-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ],
            [
                'titre' => 'Réunion mensuelle - Mars 2024',
                'date' => '2024-03-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ],
            [
                'titre' => 'Réunion mensuelle - Avril 2024',
                'date' => '2024-04-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ],
            [
                'titre' => 'Réunion mensuelle - Mai 2024',
                'date' => '2024-05-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ],
            [
                'titre' => 'Réunion mensuelle - Juin 2024',
                'date' => '2024-06-15',
                'heure' => '14:00',
                'lieu' => 'Salle de réunion principale'
            ]
        ];

        return Excel::download(new class($data) implements FromArray, WithHeadings, WithStyles {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return [
                    'titre',
                    'date',
                    'heure',
                    'lieu'
                ];
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => ['font' => ['bold' => true]],
                ];
            }
        }, 'modele_reunions.xlsx');
    }
}
