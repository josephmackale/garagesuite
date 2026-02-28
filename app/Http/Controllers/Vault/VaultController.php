<?php

namespace App\Http\Controllers\Vault;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        $garageId = $request->user()->garage_id;

        $q = MediaItem::query()
            ->where('garage_id', $garageId)
            ->latest();

        // optional filter later: type/images only
        $items = $q->paginate(24);

        return view('vault.index', compact('items'));
    }

    public function store(Request $request)
    {
        $garageId = (int) $request->user()->garage_id;

        $data = $request->validate([
            'images'   => ['required','array','min:1','max:10'],
            'images.*' => ['required','image','mimes:jpg,jpeg,png,webp','max:5120'], // 5MB
        ]);

        // Intervention Image v3
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

        $uploaded = 0;
        $duplicates = 0;

        foreach ($data['images'] as $file) {

            // Read + normalize in-memory FIRST (so hash is stable across formats)
            $img = $manager->read($file->getRealPath());

            // Fix phone rotations
            $img->orient();

            // Clamp size (no upscale)
            $img->scaleDown(width: 2000, height: 2000);

            // Encode normalized master JPEG
            $encoded = $img->toJpeg(82);
            $masterBinary = (string) $encoded;

            // Compute hash of normalized output (best dedupe)
            $hash = hash('sha256', $masterBinary);

            // If already uploaded in this garage, skip creating a duplicate media_item
            $existing = \App\Models\MediaItem::query()
                ->where('garage_id', $garageId)
                ->where('content_hash', $hash)
                ->first();

            if ($existing) {
                $duplicates++;
                continue;
            }

            // Create deterministic folder only when it's a NEW upload
            $uuid = (string) \Illuminate\Support\Str::uuid();
            $dir = "garages/{$garageId}/vault/{$uuid}";

            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($dir);

            // Save master
            $mainPath = "{$dir}/image.jpg";
            \Illuminate\Support\Facades\Storage::disk('public')->put($mainPath, $masterBinary);

            // Re-read original for thumbnail (v3-safe, avoids mutation side-effects)
            $thumbImg = $manager->read($file->getRealPath());

            $thumbImg->orient();
            $thumbImg->scaleDown(width: 400, height: 400);

            $thumbBinary = (string) $thumbImg->toJpeg(75);
            $thumbPath = "{$dir}/thumb.jpg";

            Storage::disk('public')->put($thumbPath, $thumbBinary);
            try {
                \App\Models\MediaItem::create([
                    'garage_id'     => $garageId,
                    'media_uuid'    => $uuid,
                    'disk'          => 'public',
                    'path'          => $mainPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => 'image/jpeg',
                    'size_bytes'    => \Illuminate\Support\Facades\Storage::disk('public')->size($mainPath),
                    'content_hash'  => $hash,
                ]);

                $uploaded++;
            } catch (\Illuminate\Database\QueryException $e) {
                // If a concurrent request inserted same hash first, treat as duplicate.
                // Cleanup files we wrote (best effort).
                $duplicates++;

                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete([$mainPath, $thumbPath]);
                    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory($dir);
                } catch (\Throwable $t) {
                    // ignore cleanup failures
                }
            }
        }

        $msg = "Uploaded {$uploaded} photo(s) to Photo Vault.";
        if ($duplicates > 0) {
            $msg .= " Skipped {$duplicates} duplicate(s).";
        }

        return redirect()->route('vault.index')->with('success', $msg);
    }
}
