<?php

namespace App\Http\Controllers\Vault;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Vehicle;
use App\Models\MediaItem;
use App\Models\MediaAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VaultAttachController extends Controller
{
    private function assertSameGarage(Request $request, $model): void
    {
        abort_unless($model->garage_id === $request->user()->garage_id, 403);
    }

    private function jsonOrRedirect(Request $request, array $json, string $flashMessage)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($json);
        }

        return back()->with('success', $flashMessage);
    }

    /**
     * Build "attached" payload for inspection.js:
     * Each item MUST include:
     * - attachment_id (for detach)
     * - id (media_item_id) (your JS uses row.id as media_item_id)
     * - url (thumbnail)
     */
    private function attachedForJob(Request $request, Job $job, string $label): array
    {
        $garageId = (int) $request->user()->garage_id;

        $rows = MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', Job::class)
            ->where('attachable_id', $job->id)
            ->where('label', $label)
            ->orderByDesc('id')
            ->get(['id', 'media_item_id', 'label']);

        if ($rows->isEmpty()) return [];

        $mediaIds = $rows->pluck('media_item_id')->unique()->values()->all();

        $media = MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $mediaIds)
            ->get(['id', 'disk', 'path', 'mime_type', 'original_name'])
            ->keyBy('id');

        return $rows->map(function ($ma) use ($media) {
            $mi = $media->get($ma->media_item_id);

            $disk = $mi?->disk ?: 'public';
            $path = $mi?->path ?: '';
            $url  = $path !== '' ? Storage::disk($disk)->url($path) : '';

            return [
                'attachment_id' => (int) $ma->id,
                'id'            => (int) $ma->media_item_id, // JS expects row.id == media_item_id
                'disk'          => $disk,
                'path'          => $path,
                'url'           => $url,
                'mime_type'     => $mi?->mime_type,
                'name'          => $mi?->original_name,
                'label'         => (string) $ma->label,
            ];
        })->values()->all();
    }

    public function attachToJob(Request $request, Job $job)
    {
        $this->assertSameGarage($request, $job);

        $data = $request->validate([
            'media_item_ids'   => ['required', 'array', 'min:1'],
            'media_item_ids.*' => ['integer', 'exists:media_items,id'],
            'label'            => ['nullable', 'string', 'max:50'],
        ]);

        $label = $data['label'] ?? 'inspection';
        $garageId = (int) $request->user()->garage_id;

        // Only attach media that belongs to this garage (critical safety)
        $items = MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $data['media_item_ids'])
            ->get(['id']);

        $newlyCreated = 0;

        foreach ($items as $item) {
            $row = MediaAttachment::firstOrCreate([
                'garage_id'       => $garageId,
                'media_item_id'   => $item->id,
                'attachable_type' => Job::class,
                'attachable_id'   => $job->id,
                'label'           => $label,
            ]);

            if ($row->wasRecentlyCreated) $newlyCreated++;
        }

        // ✅ What the UI needs to render immediately
        $attached = $this->attachedForJob($request, $job, $label);

        return $this->jsonOrRedirect(
            $request,
            [
                'ok'              => true,
                'message'         => 'Photos attached to job.',
                'label'           => $label,
                'attached'        => $attached,
                'photos_count'    => count($attached),
                'attached_count'  => $newlyCreated,
                'total_selected'  => $items->count(),
                'attachable'      => ['type' => 'job', 'id' => $job->id],
            ],
            'Photos attached to job.'
        );
    }

    public function detachFromJob(Request $request, Job $job, MediaItem $mediaItem)
    {
        $this->assertSameGarage($request, $job);
        abort_unless($mediaItem->garage_id === $request->user()->garage_id, 403);

        $label = (string) ($request->input('label') ?: 'inspection');

        if ($label === 'inspection') {
            $label = 'inspection_photo'; // Normalize to Approval contract
        }

        // Delete all attachments for this media on this job (simple + safe)
        $deleted = MediaAttachment::where('garage_id', $request->user()->garage_id)
            ->where('media_item_id', $mediaItem->id)
            ->where('attachable_type', Job::class)
            ->where('attachable_id', $job->id)
            ->when($label, fn($q) => $q->where('label', $label))
            ->delete();

        $attached = $this->attachedForJob($request, $job, $label);

        return $this->jsonOrRedirect(
            $request,
            [
                'ok'            => true,
                'message'       => 'Photo detached from job.',
                'deleted_count' => (int) $deleted,
                'label'         => $label,
                'attached'      => $attached,
                'photos_count'  => count($attached),
                'attachable'    => ['type' => 'job', 'id' => $job->id],
                'media_item_id' => $mediaItem->id,
            ],
            'Photo detached from job.'
        );
    }

    public function attachToVehicle(Request $request, Vehicle $vehicle)
    {
        $this->assertSameGarage($request, $vehicle);

        $data = $request->validate([
            'media_item_ids'   => ['required', 'array', 'min:1'],
            'media_item_ids.*' => ['integer', 'exists:media_items,id'],
            'label'            => ['nullable', 'string', 'max:50'],
        ]);

        $label = $data['label'] ?? null;
        $garageId = (int) $request->user()->garage_id;

        $items = MediaItem::where('garage_id', $garageId)
            ->whereIn('id', $data['media_item_ids'])
            ->get(['id']);

        $attached = 0;

        foreach ($items as $item) {
            $attrs = [
                'garage_id'       => $garageId,
                'media_item_id'   => $item->id,
                'attachable_type' => Vehicle::class,
                'attachable_id'   => $vehicle->id,
            ];

            if ($label !== null && $label !== '') {
                $attrs['label'] = $label;
            }

            $row = MediaAttachment::firstOrCreate($attrs);
            if ($row->wasRecentlyCreated) $attached++;
        }

        return $this->jsonOrRedirect(
            $request,
            [
                'ok'             => true,
                'message'        => 'Photos attached to vehicle.',
                'attached_count' => $attached,
                'total_selected' => $items->count(),
                'label'          => $label,
                'attachable'     => ['type' => 'vehicle', 'id' => $vehicle->id],
            ],
            'Photos attached to vehicle.'
        );
    }

    public function detachFromVehicle(Request $request, Vehicle $vehicle, MediaItem $mediaItem)
    {
        $this->assertSameGarage($request, $vehicle);
        abort_unless($mediaItem->garage_id === $request->user()->garage_id, 403);

        $deleted = MediaAttachment::where('garage_id', $request->user()->garage_id)
            ->where('media_item_id', $mediaItem->id)
            ->where('attachable_type', Vehicle::class)
            ->where('attachable_id', $vehicle->id)
            ->delete();

        return $this->jsonOrRedirect(
            $request,
            [
                'ok'            => true,
                'message'       => 'Photo detached from vehicle.',
                'deleted_count' => (int) $deleted,
                'attachable'    => ['type' => 'vehicle', 'id' => $vehicle->id],
                'media_item_id' => $mediaItem->id,
            ],
            'Photo detached from vehicle.'
        );
    }
}