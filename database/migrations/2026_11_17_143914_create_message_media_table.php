<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHUNK_SIZE = 5 * 1024 * 1024;
    private const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;

    public function up(): void
    {
        Schema::create('message_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('disk')->default('public');
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('upload_id')->nullable()->unique();
            $table->unsignedInteger('chunk_size')->default(self::CHUNK_SIZE);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('uploaded_chunks')->default(0);
            $table->unsignedBigInteger('total_size')->default(0);
            $table->enum('status', ['uploading', 'assembling', 'completed', 'failed'])->default('uploading');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('upload_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_media');
    }
};