<?php

namespace App\Helpers;

class VersionHelper
{
    /**
     * Récupère la version de l'application
     */
    public static function getVersion(): string
    {
        $versionFile = base_path('VERSION');
        
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        
        // Fallback vers le composer.json
        $composerFile = base_path('composer.json');
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }
        
        return '1.0.0';
    }
    
    /**
     * Récupère la version avec des informations supplémentaires
     */
    public static function getVersionInfo(): array
    {
        return [
            'version' => self::getVersion(),
            'build_date' => date('Y-m-d H:i:s'),
            'git_commit' => self::getGitCommit(),
            'environment' => app()->environment(),
        ];
    }
    
    /**
     * Récupère le commit Git actuel
     */
    public static function getGitCommit(): string
    {
        $gitDir = base_path('.git');
        
        if (!is_dir($gitDir)) {
            return 'unknown';
        }
        
        $headFile = $gitDir . '/HEAD';
        if (!file_exists($headFile)) {
            return 'unknown';
        }
        
        $head = trim(file_get_contents($headFile));
        
        // Si c'est une référence
        if (strpos($head, 'ref:') === 0) {
            $ref = substr($head, 5);
            $refFile = $gitDir . '/' . $ref;
            if (file_exists($refFile)) {
                return substr(trim(file_get_contents($refFile)), 0, 7);
            }
        }
        
        // Si c'est directement un commit
        if (strlen($head) >= 7) {
            return substr($head, 0, 7);
        }
        
        return 'unknown';
    }
    
    /**
     * Met à jour la version
     */
    public static function updateVersion(string $newVersion): bool
    {
        $versionFile = base_path('VERSION');
        return file_put_contents($versionFile, $newVersion . "\n") !== false;
    }
}
