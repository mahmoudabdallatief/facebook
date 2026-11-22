<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageMember extends Model
{
    protected $fillable = [
        'page_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const ROLES = ['admin', 'moderator', 'member'];

    public static function validRole(string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }
}
