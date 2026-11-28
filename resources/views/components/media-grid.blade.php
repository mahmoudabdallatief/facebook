@props([
    'media',
    'heightClass' => 'h-64',
    'roundedClass' => '',
])

@php
    $items = $media->values();
    $total = $items->count();
    $maxVisible = 4;
    $visibleItems = $items->take($maxVisible);
    $extraItems = $items->slice($maxVisible);
    $extraCount = $extraItems->count();

    $gridCols = match (true) {
        $total === 1 => 'grid-cols-1',
        $total === 2 => 'grid-cols-2',
        $total >= 4 => 'grid-cols-2',
        default => 'grid-cols-2 sm:grid-cols-3',
    };

    $formatBytes = static function (?int $bytes): ?string {
        if (! $bytes || $bytes <= 0) {
            return null;
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return ($power >= 2 ? number_format($value, 1) : (string) round($value)) . ' ' . $units[$power];
    };

    $formatDuration = static function ($seconds): ?string {
        if (! is_numeric($seconds) || (float) $seconds <= 0) {
            return null;
        }
        $secs = (int) round((float) $seconds);
        $hours = intdiv($secs, 3600);
        $mins = intdiv($secs % 3600, 60);
        $rem = $secs % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $mins, $rem)
            : sprintf('%02d:%02d', $mins, $rem);
    };
@endphp

@if($total > 0)
    <div class="space-y-1" data-media-gallery>
        <div class="grid gap-1 {{ $gridCols }}">
            @foreach($visibleItems as $index => $item)
                @php
                    $isLastWithExtra = $index === ($maxVisible - 1) && $extraCount > 0;
                @endphp
                <div class="relative overflow-hidden bg-zinc-200 dark:bg-zinc-800 {{ $heightClass }} {{ $roundedClass }}">
                    <x-media-item
                        :item="$item"
                        :height-class="$heightClass"
                        :rounded-class="$roundedClass"
                        :size-label="$formatBytes($item->total_size)"
                        :duration-label="$formatDuration($item->metadata['duration'] ?? null)"
                    />

                    @if($isLastWithExtra)
                        <button
                            type="button"
                            data-expand-media-grid
                            aria-label="{{ __('app.show_more_media', ['count' => $extraCount]) }}"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-black/60 text-2xl font-bold text-white transition hover:bg-black/70"
                        >
                            +{{ $extraCount }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        @if($extraCount > 0)
            <div class="hidden grid gap-1 grid-cols-2 sm:grid-cols-3" data-extra-media-container>
                @foreach($extraItems as $item)
                    <div class="relative overflow-hidden bg-zinc-200 dark:bg-zinc-800 {{ $heightClass }} {{ $roundedClass }}">
                        <x-media-item
                            :item="$item"
                            :height-class="$heightClass"
                            :rounded-class="$roundedClass"
                            :deferred="true"
                            :size-label="$formatBytes($item->total_size)"
                            :duration-label="$formatDuration($item->metadata['duration'] ?? null)"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
