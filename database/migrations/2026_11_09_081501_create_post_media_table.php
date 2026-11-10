<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video']);
            $table->string('disk', 20)->default('public');
            $table->string('path');
            $table->string('url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
