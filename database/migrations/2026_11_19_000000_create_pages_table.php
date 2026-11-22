<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('page'); // page, group
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('avatar')->nullable();
            $table->string('privacy')->default('public'); // public, closed, private
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['type']);
            $table->index(['slug']);
            $table->index(['privacy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
