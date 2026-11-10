<?php

namespace App\Jobs;

use App\Models\PostMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public PostMedia $media
    ) {}

    public function handle(): void
    {
        if ($this->media->type !== 'video') {
            return;
        }

        $this->generateThumbnail();
        $this->extractMetadata();
        $this->optimizeIfNeeded();
    }

    private function generateThumbnail(): void
    {
        $inputPath = Storage::disk('public')->path($this->media->path);
        $thumbnailName = Str::slug(pathinfo($this->media->path, PATHINFO_FILENAME)).'_thumb.jpg';
        $thumbnailPath = dirname($this->media->path).'/'.$thumbnailName;

        $process = new Process([
            'ffmpeg',
            '-i', $inputPath,
            '-ss', '00:00:01',
            '-vframes', '1',
            '-vf', 'scale=640:-1',
            '-f', 'image2',
            Storage::disk('public')->path($thumbnailPath),
        ]);

        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $this->media->metadata = array_merge($this->media->metadata ?? [], [
                'thumbnail' => 'storage/'.$thumbnailPath,
            ]);
            $this->media->save();
        }
    }

    private function extractMetadata(): void
    {
        $inputPath = Storage::disk('public')->path($this->media->path);

        $process = new Process([
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $inputPath,
        ]);

        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful()) {
            $data = json_decode($process->getOutput(), true);
            $videoStream = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video');

            $this->media->metadata = array_merge($this->media->metadata ?? [], [
                'duration' => $data['format']['duration'] ?? null,
                'bitrate' => $data['format']['bit_rate'] ?? null,
                'width' => $videoStream['width'] ?? null,
                'height' => $videoStream['height'] ?? null,
                'codec' => $videoStream['codec_name'] ?? null,
            ]);
            $this->media->save();
        }
    }

    private function optimizeIfNeeded(): void
    {
        $inputPath = Storage::disk('public')->path($this->media->path);
        $fileSize = Storage::disk('public')->size($this->media->path);

        if ($fileSize > 100 * 1024 * 1024) {
            $optimizedName = Str::slug(pathinfo($this->media->path, PATHINFO_FILENAME)).'_optimized.mp4';
            $optimizedPath = dirname($this->media->path).'/'.$optimizedName;

            $process = new Process([
                'ffmpeg',
                '-i', $inputPath,
                '-c:v', 'libx264',
                '-crf', '28',
                '-preset', 'fast',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-movflags', '+faststart',
                Storage::disk('public')->path($optimizedPath),
            ]);

            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful() && Storage::disk('public')->exists($optimizedPath)) {
                $optimizedSize = Storage::disk('public')->size($optimizedPath);

                if ($optimizedSize < $fileSize) {
                    Storage::disk('public')->delete($this->media->path);
                    Storage::disk('public')->move($optimizedPath, $this->media->path);
                    $this->media->metadata = array_merge($this->media->metadata ?? [], [
                        'optimized' => true,
                        'original_size' => $fileSize,
                        'optimized_size' => $optimizedSize,
                    ]);
                    $this->media->save();
                } else {
                    Storage::disk('public')->delete($optimizedPath);
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->media->metadata = array_merge($this->media->metadata ?? [], [
            'processing_error' => $exception->getMessage(),
        ]);
        $this->media->save();
    }
}
