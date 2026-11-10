<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_media', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('post_media', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->foreignId('post_id')->nullable(false)->change();
        });
    }
};
