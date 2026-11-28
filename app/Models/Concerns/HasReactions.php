<?php

namespace App\Models\Concerns;

use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function reactionCounts(): array
    {
        if ($this->relationLoaded('reactions')) {
            $grouped = $this->reactions->countBy('reaction_type');
            $counts = [];

            foreach (Reaction::REACTION_TYPES as $type) {
                $counts[$type] = (int) $grouped->get($type, 0);
            }

            return $counts;
        }

        $rawCounts = $this->reactions()
            ->selectRaw('reaction_type, COUNT(*) as aggregate')
            ->groupBy('reaction_type')
            ->pluck('aggregate', 'reaction_type');

        $counts = [];
        foreach (Reaction::REACTION_TYPES as $type) {
            $counts[$type] = (int) ($rawCounts[$type] ?? 0);
        }

        return $counts;
    }

    public function userReaction(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->relationLoaded('reactions')) {
            return $this->reactions->firstWhere('user_id', $user->id)?->reaction_type;
        }

        return $this->reactions()->where('user_id', $user->id)->value('reaction_type');
    }

    public function totalReactions(): int
    {
        if ($this->relationLoaded('reactions')) {
            return $this->reactions->count();
        }

        return $this->reactions()->count();
    }
}
