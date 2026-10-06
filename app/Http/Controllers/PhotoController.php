<?php

namespace App\Http\Controllers;

use App\Models\ReportPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Foto bukti disimpan di disk privat dan hanya bisa dibuka lewat controller ini,
 * jadi hanya pemilik laporan dan Admin/Toolman yang bisa melihatnya.
 */
class PhotoController extends Controller
{
    public function show(ReportPhoto $photo): BinaryFileResponse
    {
        return $this->serve($photo, $photo->path);
    }

    public function thumb(ReportPhoto $photo): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $path = $photo->thumb_path && $disk->exists($photo->thumb_path) ? $photo->thumb_path : $photo->path;

        return $this->serve($photo, $path);
    }

    private function serve(ReportPhoto $photo, string $path): BinaryFileResponse
    {
        Gate::authorize('view', $photo->report);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
