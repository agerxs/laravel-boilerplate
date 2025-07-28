<?php
namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AppVersionController extends Controller
{
    public function adminIndex()
    {
        $versions = AppVersion::orderByDesc('version_code')->get();
        return Inertia::render('Admin/AppVersions/Index', [
            'versions' => $versions
        ]);
    }

    public function adminStore(Request $request)
    {
        $request->validate([
            'version_code' => 'required|integer|unique:app_versions',
            'version_name' => 'required|string',
            'apk_file' => 'required|file|mimes:apk',
            'release_notes' => 'nullable|string',
        ]);
        $path = $request->file('apk_file')->store('apks', 'public');
        AppVersion::create([
            'version_code' => $request->version_code,
            'version_name' => $request->version_name,
            'apk_file' => $path,
            'release_notes' => $request->release_notes,
        ]);
        return redirect()->route('admin.app_versions.index')->with('success', 'APK uploadé avec succès');
    }

    public function adminDestroy($id)
    {
        $version = AppVersion::findOrFail($id);
        Storage::disk('public')->delete($version->apk_file);
        $version->delete();
        return redirect()->route('admin.app_versions.index')->with('success', 'Version supprimée');
    }
} 