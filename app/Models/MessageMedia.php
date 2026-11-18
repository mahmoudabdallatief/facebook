<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageMedia extends Model
{
    use HasFactory;

    private const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;
    private const MAX_IMAGE_SIZE = 50 * 1024 * 1024;

    protected $fillable = [
        'message_id',
        'user_id',
        'type',
        'disk',
        'path',
        'url',
        'order',
        'upload_id',
        'chunk_size',
        'total_chunks',
        'uploaded_chunks',
        'total_size',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'chunk_size' => 'integer',
            'total_chunks' => 'integer',
            'uploaded_chunks' => 'integer',
            'total_size' => 'integer',
        ];
    }

    public function getUrlAttribute(?string $value): string
    {
        $path = $this->path;

        return $this->disk === 'public'
            ? asset('storage/' . $path)
            : asset($path);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed' && $this->uploaded_chunks >= $this->total_chunks;
    }

    public function getProgress(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        return ($this->uploaded_chunks / $this->total_chunks) * 100;
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createFromUpload(array $data, User $user): self
    {
        $type = $data['type'];
        $file = $data['file'];
        
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $slug = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $finalPath = "messages/{$user->id}/" . Str::uuid() . '_' . $slug . '.' . $extension;

        $file->storeAs('public', $finalPath);

        $mimeType = $file->getMimeType();
        $allowedMimeTypes = $type === 'video'
            ? ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska']
            : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            Storage::disk('public')->delete($finalPath);
            throw new \InvalidArgumentException('Invalid file type');
        }

        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'disk' => 'public',
            'path' => $finalPath,
            'url' => 'storage/' . $finalPath,
            'order' => 0,
            'metadata' => [
                'original_filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
            ],
        ]);
    }
}