<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GarageLogoController extends Controller
{
    public function store(Request $request)
    {
        $garage = Auth::user()->garage;

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        // Always store ONE canonical logo path
        $path = "garages/{$garage->id}/logo.png";

        // Delete old logo if exists (any format)
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $old = "garages/{$garage->id}/logo.$ext";
            if (Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        $file = $request->file('logo');

        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file->getPathname());

        // Resize safely (Intervention v3)
        $image = $image->scaleDown(800, 800);

        // ALWAYS encode to PNG (keeps transparency)
        $encoded = $image->toPng();

        Storage::disk('public')->put($path, $encoded);

        // Save canonical path
        $garage->logo_path = $path;
        $garage->save();

        return back()->with('success', 'Logo uploaded successfully.');
    }

    public function destroy()
    {
        $garage = Auth::user()->garage;

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = "garages/{$garage->id}/logo.$ext";
            if (Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }

        $garage->logo_path = null;
        $garage->save();

        return back()->with('success', 'Logo removed.');
    }
}
