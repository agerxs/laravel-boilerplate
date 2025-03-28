<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\AttendancePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class AttendancePhotoController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const COMPRESSION_THRESHOLD = 2 * 1024 * 1024; // 2MB
    private const MIN_WIDTH = 800;
    private const MIN_HEIGHT = 600;
    private const COMPRESSION_QUALITY = 80;

    public function store(Request $request, Attendee $attendee)
    {
        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpeg,png',
                'max:' . self::MAX_FILE_SIZE,
                'dimensions:min_width=' . self::MIN_WIDTH . ',min_height=' . self::MIN_HEIGHT
            ]
        ]);

        $photo = $request->file('photo');
        $originalSize = $photo->getSize();
        
        // Générer un nom unique pour le fichier
        $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
        $thumbnailFilename = 'thumb_' . $filename;

        // Sauvegarder l'original
        $originalPath = $photo->storeAs('attendance-photos/originals', $filename, 'public');

        // Créer et sauvegarder la version compressée si nécessaire
        if ($originalSize > self::COMPRESSION_THRESHOLD) {
            $image = Image::make($photo);
            $image->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $compressedPath = 'attendance-photos/thumbnails/' . $thumbnailFilename;
            Storage::disk('public')->put($compressedPath, $image->encode(null, self::COMPRESSION_QUALITY));
            $compressedSize = Storage::disk('public')->size($compressedPath);
        } else {
            $compressedPath = $originalPath;
            $compressedSize = $originalSize;
        }

        // Créer l'enregistrement dans la base de données
        $attendancePhoto = AttendancePhoto::create([
            'attendee_id' => $attendee->id,
            'photo_url' => Storage::disk('public')->url($originalPath),
            'thumbnail_url' => Storage::disk('public')->url($compressedPath),
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'taken_at' => now(),
        ]);

        return response()->json($attendancePhoto);
    }

    public function show(AttendancePhoto $photo)
    {
        return response()->file(Storage::disk('public')->path($photo->photo_url));
    }

    public function thumbnail(AttendancePhoto $photo)
    {
        return response()->file(Storage::disk('public')->path($photo->thumbnail_url));
    }

    public function destroy(AttendancePhoto $photo)
    {
        // Supprimer les fichiers
        Storage::disk('public')->delete($photo->photo_url);
        if ($photo->photo_url !== $photo->thumbnail_url) {
            Storage::disk('public')->delete($photo->thumbnail_url);
        }

        // Supprimer l'enregistrement
        $photo->delete();

        return response()->json(['message' => 'Photo supprimée avec succès']);
    }
} 