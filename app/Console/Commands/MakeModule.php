<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;


class MakeModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Génère un module complet (CRUD, API, Filament) dans app/Modules';

    public function handle()
    {
        $name = ucfirst($this->argument('name')); // Nom du module
        $modulePath = base_path("app/Modules/$name");

        if (File::exists($modulePath)) {
            $this->error("Le module $name existe déjà !");
            return;
        }

        // 📌 1. Créer l’arborescence du module
        File::makeDirectory("$modulePath/Controllers", 0755, true);
        File::makeDirectory("$modulePath/Models", 0755, true);
        File::makeDirectory("$modulePath/Requests", 0755, true);
        File::makeDirectory("$modulePath/Resources", 0755, true);
        File::put("$modulePath/routes.php", "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::apiResource('" . strtolower($name) . "', \\App\\Controllers\\{$name}Controller::class);");

        $this->info("✅ Routes API ajoutées");

        $this->info("✅ Dossiers créés pour le module $name");

        Artisan::call("make:model App/Models/$name -m");
        $this->info("✅ Modèle et migration créés");

        Artisan::call("make:controller App/Controllers/{$name}Controller --api --model=App/Models/$name");
        $this->info("✅ Contrôleur API REST créé");
        
        Artisan::call("make:request App/Requests/{$name}StoreRequest");
        Artisan::call("make:request App/Requests/{$name}UpdateRequest");
        $this->info("✅ Requêtes de validation créées");
        
        Artisan::call("make:filament-resource $name");

       // Définir $resourceName correctement pour l'utiliser dans les messages
        $resourceName = "{$name}Resource"; // Le nom de la ressource

        $this->info("✅ Filament Resource créé : $resourceName");

        $filamentResourcePath = app_path("Filament/Resources/{$resourceName}.php");
        if (File::exists($filamentResourcePath)) {
            $this->info("📌 Fichier Filament trouvé, mise à jour possible.");
        } else {
            $this->error("⚠️ Fichier Filament non trouvé, vérifiez l'installation.");
        }

        $this->info("🎉 Module CRUD $name généré avec API REST et Filament !");    
    }
}
