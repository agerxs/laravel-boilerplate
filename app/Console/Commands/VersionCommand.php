<?php

namespace App\Console\Commands;

use App\Helpers\VersionHelper;
use Illuminate\Console\Command;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version {action=show : Action to perform (show|set|bump)} {version? : Version to set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage application version';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'show':
                $this->showVersion();
                break;
            case 'set':
                $this->setVersion();
                break;
            case 'bump':
                $this->bumpVersion();
                break;
            default:
                $this->error('Action invalide. Utilisez: show, set, ou bump');
                return 1;
        }

        return 0;
    }

    /**
     * Affiche la version actuelle
     */
    private function showVersion()
    {
        $versionInfo = VersionHelper::getVersionInfo();
        
        $this->info('=== Informations de Version ===');
        $this->line('Version: ' . $versionInfo['version']);
        $this->line('Build Date: ' . $versionInfo['build_date']);
        $this->line('Git Commit: ' . $versionInfo['git_commit']);
        $this->line('Environment: ' . $versionInfo['environment']);
    }

    /**
     * Définit une nouvelle version
     */
    private function setVersion()
    {
        $version = $this->argument('version');
        
        if (!$version) {
            $version = $this->ask('Entrez la nouvelle version (ex: 1.2.3)');
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->error('Format de version invalide. Utilisez le format: X.Y.Z');
            return 1;
        }

        if (VersionHelper::updateVersion($version)) {
            $this->info("Version mise à jour vers: {$version}");
        } else {
            $this->error('Erreur lors de la mise à jour de la version');
            return 1;
        }
    }

    /**
     * Incrémente la version
     */
    private function bumpVersion()
    {
        $currentVersion = VersionHelper::getVersion();
        $parts = explode('.', $currentVersion);
        
        if (count($parts) !== 3) {
            $this->error('Version actuelle invalide: ' . $currentVersion);
            return 1;
        }

        $type = $this->choice(
            'Quel type de version incrémenter?',
            ['patch', 'minor', 'major'],
            'patch'
        );

        switch ($type) {
            case 'major':
                $parts[0]++;
                $parts[1] = 0;
                $parts[2] = 0;
                break;
            case 'minor':
                $parts[1]++;
                $parts[2] = 0;
                break;
            case 'patch':
                $parts[2]++;
                break;
        }

        $newVersion = implode('.', $parts);
        
        if (VersionHelper::updateVersion($newVersion)) {
            $this->info("Version incrémentée de {$currentVersion} vers {$newVersion}");
        } else {
            $this->error('Erreur lors de la mise à jour de la version');
            return 1;
        }
    }
}
