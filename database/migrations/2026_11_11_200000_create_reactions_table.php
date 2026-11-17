<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction_type', 20); // like, love, haha, wow, sad, angry
            $table->morphs('reactionable');
            $table->timestamps();

            $table->unique(['user_id', 'reactionable_type', 'reactionable_id']);
            $table->index(['reactionable_type', 'reactionable_id', 'reaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
