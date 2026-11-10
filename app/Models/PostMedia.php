<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    protected $fillable = [
        'post_id',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'chunk_size' => 'integer',
            'total_chunks' => 'integer',
            'uploaded_chunks' => 'integer',
            'total_size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
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
}
