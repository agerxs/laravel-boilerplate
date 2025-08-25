<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppVersionController extends Controller
{
    public function index()
    {
        return response()->json(AppVersion::orderByDesc('version_code')->get());
    }

    public function show($id)
    {
        $version = AppVersion::findOrFail($id);
        return response()->json($version);
    }

    public function latest()
    {
        $version = AppVersion::orderByDesc('version_code')->first();
        return response()->json($version);
    }

    public function checkUpdate($currentVersion)
    {
        $currentVersionCode = (int) $currentVersion;
        $latestVersion = AppVersion::orderByDesc('version_code')->first();

        if (!$latestVersion) {
            return response()->json([
                'update_available' => false,
                'message' => 'Aucune version disponible'
            ]);
        }

        $updateAvailable = $latestVersion->version_code > $currentVersionCode;

        return response()->json([
            'update_available' => $updateAvailable,
            'current_version' => $currentVersionCode,
            'latest_version' => $latestVersion->version_code,
            'latest_version_name' => $latestVersion->version_name,
            'download_url' => $updateAvailable ? url('/storage/' . $latestVersion->apk_file) : null,
            'release_notes' => $latestVersion->release_notes,
            'force_update' => false, // Vous pouvez ajouter ce champ dans la table si nécessaire
            'message' => $updateAvailable 
                ? 'Une nouvelle version est disponible' 
                : 'Vous avez la dernière version'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'version_code' => 'required|integer|unique:app_versions',
            'version_name' => 'required|string',
            'apk_file' => 'required|file|mimes:apk',
            'release_notes' => 'nullable|string',
        ]);

        $path = $request->file('apk_file')->store('apks', 'public');

        $version = AppVersion::create([
            'version_code' => $request->version_code,
            'version_name' => $request->version_name,
            'apk_file' => $path,
            'release_notes' => $request->release_notes,
        ]);

        return response()->json($version, 201);
    }
} 