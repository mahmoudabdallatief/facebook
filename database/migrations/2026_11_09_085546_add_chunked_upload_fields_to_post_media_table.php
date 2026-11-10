<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->string('upload_id')->nullable()->index()->after('order');
            $table->unsignedBigInteger('chunk_size')->default(0)->after('upload_id');
            $table->unsignedBigInteger('total_chunks')->default(0)->after('chunk_size');
            $table->unsignedBigInteger('uploaded_chunks')->default(0)->after('total_chunks');
            $table->unsignedBigInteger('total_size')->default(0)->after('uploaded_chunks');
            $table->string('status')->default('pending')->after('total_size');
            $table->json('metadata')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->dropColumn(['upload_id', 'chunk_size', 'total_chunks', 'uploaded_chunks', 'total_size', 'status', 'metadata']);
        });
    }
};
