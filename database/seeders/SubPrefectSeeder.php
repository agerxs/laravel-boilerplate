<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Locality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SubPrefectSeeder extends Seeder
{
    public function run()
    {
        // Créer le rôle sous-prefet s'il n'existe pas
        $subPrefectRole = Role::firstOrCreate(['name' => 'sous-prefet']);
        
        // Charger les données des présidents
        $subPrefectsData = json_decode(file_get_contents(resource_path('data/sous-pref_dates_harmonisees_v2.json')), true);
        
        $phones = [];
        
        Log::info('Début de la création des présidents');
        
        foreach ($subPrefectsData as $subPrefect) {
            // Vérifier si le nom et le contact sont renseignés
            if (empty(trim($subPrefect['Nom du président'])) ) {
                Log::info("Ignoré : Sous-préfecture {$subPrefect['Sous-Préfecture']} - Nom non renseigné");
                continue;
            }
            if(empty(trim($subPrefect['Contact '])))
            {
                Log::info("Ignoré : Sous-préfecture {$subPrefect['Sous-Préfecture']} - Contact non renseigné");
               
            }

            // Nettoyer le numéro de téléphone
            $phone = preg_replace('/[^0-9]/', '', $subPrefect['Contact ']);
            
            // Vérifier si l'utilisateur existe déjà par téléphone
            //$existingUser = User::where('phone', $phone)->first();
            //if ($existingUser) {
            //    $message = "Ignoré : {$subPrefect['Nom du président']} - Numéro de téléphone déjà existant";
            //    echo $message . "\n";
            //    Log::info($message);
            //    continue;
            //}

            // Trouver la localité (sous-préfecture)
            $locality = Locality::whereHas('type', function($query) {
                $query->where('name', 'subprefecture');
            })
            ->where('name', trim($subPrefect['Sous-Préfecture']))
            ->first();

            if (!$locality) {
                $message = "Ignoré : {$subPrefect['Nom du président']} - Localité non trouvée : {$subPrefect['Sous-Préfecture']}";
                echo $message . "\n";
                Log::warning($message);
                continue;
            }

            try {
                // Générer un email à partir du nom
                $name = trim($subPrefect['Nom du président']);
                $email = Str::slug($name, '.') . '@admin.ci';

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt('password'),
                    'locality_id' => $locality->id,
                    'phone' => $phone,
                    'whatsapp' => $phone,
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]);

                // Assigner le rôle sous-prefet
                $user->assignRole('sous-prefet');
                
                $message = "Créé : {$name} - {$subPrefect['Sous-Préfecture']}";
                echo $message . "\n";
                Log::info($message);
            } catch (\Exception $e) {
                $message = "Erreur lors de la création de {$name} : {$e->getMessage()}";
                echo $message . "\n";
                Log::error($message);
            }
        }
        
        Log::info('Fin de la création des présidents');
    }
} 