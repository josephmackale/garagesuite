<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\MediaItem;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;

class BackfillMediaItemHashes extends Command
{
    protected $signature = 'media:backfill-hashes {--limit=0 : Process only N items (0 = all)}';
    protected $description = 'Backfill deterministic content hashes for existing media items (and auto-merge duplicates)';

    public function handle(): int
    {
        $manager = new ImageManager(new Driver());

        $q = MediaItem::query()
            ->whereNull('content_hash')
            ->orderBy('id');

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $q->limit($limit);
        }

        $total = $q->count();
        $this->info("Found {$total} items to process");

        if ($total === 0) {
            $this->info('Nothing to do.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $q->chunkById(50, function ($items) use ($manager, $bar) {
            foreach ($items as $item) {
                try {
                    $disk = $item->disk ?: 'public';
                    $path = (string) $item->path;

                    if ($path === '' || !Storage::disk($disk)->exists($path)) {
                        $this->warn("Missing file for media_id={$item->id} path={$path}");
                        $bar->advance();
                        continue;
                    }

                    // local disk assumed
                    $abs = Storage::disk($disk)->path($path);

                    $img = $manager->read($abs);
                    $img->orient();
                    $img->scaleDown(width: 2000, height: 2000);

                    $encoded = $img->encode(new JpegEncoder(quality: 82));
                    $hash = hash('sha256', (string) $encoded);

                    $canonicalId = MediaItem::query()
                        ->where('garage_id', (int) $item->garage_id)
                        ->where('content_hash', $hash)
                        ->value('id');

                    if ($canonicalId) {
                        DB::transaction(function () use ($item, $canonicalId) {
                            // Dedup-safe move of attachments:
                            $rows = DB::table('media_attachments')
                                ->where('garage_id', (int) $item->garage_id)
                                ->where('media_item_id', (int) $item->id)
                                ->get();

                            foreach ($rows as $r) {
                                $exists = DB::table('media_attachments')
                                    ->where('garage_id', (int) $item->garage_id)
                                    ->where('media_item_id', (int) $canonicalId)
                                    ->where('attachable_type', (string) $r->attachable_type)
                                    ->where('attachable_id', (int) $r->attachable_id)
                                    ->exists();

                                if ($exists) {
                                    DB::table('media_attachments')
                                        ->where('id', (int) $r->id)
                                        ->delete();
                                }
                            }

                            DB::table('media_attachments')
                                ->where('garage_id', (int) $item->garage_id)
                                ->where('media_item_id', (int) $item->id)
                                ->update(['media_item_id' => (int) $canonicalId]);

                            $item->delete(); // forceDelete() if no soft deletes
                        });

                        $this->warn("Merged duplicate media_id={$item->id} -> canonical={$canonicalId}");
                        $bar->advance();
                        continue;
                    }

                    $item->content_hash = $hash;
                    $item->save();
                } catch (\Throwable $e) {
                    $this->error("Error on item {$item->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}