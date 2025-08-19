<?php
namespace Database\Seeders\Models;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --model-mode --models=LocalityType,Locality
         *
         */

        
        $newData0 = \App\Models\LocalityType::create([
            'id' => 1,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'PAYS',
            'updated_at' => NULL,
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
        $newData1 = \App\Models\LocalityType::create([
            'id' => 2,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'DISTRICT',
            'updated_at' => NULL,
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
        $newData2 = \App\Models\LocalityType::create([
            'id' => 3,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'REGION',
            'updated_at' => NULL,
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
        $newData3 = \App\Models\LocalityType::create([
            'id' => 4,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'departement',
            'updated_at' => '2025-03-19 22:39:30',
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
        $newData4 = \App\Models\LocalityType::create([
            'id' => 5,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'sub_prefecture',
            'updated_at' => '2025-03-19 22:27:35',
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
        $newData5 = \App\Models\LocalityType::create([
            'id' => 6,
            'created_at' => '2025-03-19 17:44:57',
            'name' => 'village',
            'updated_at' => '2025-03-19 22:36:43',
            'type_localite_id' => NULL,
            'deleted_at' => NULL,
        ]);
    }
}