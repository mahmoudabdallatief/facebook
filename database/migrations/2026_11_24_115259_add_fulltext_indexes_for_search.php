<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing') || config('database.default') !== 'mysql') {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->fulltext(['name', 'email']);
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->fulltext('body');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->fulltext('name');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->fulltext('name');
        });
    }

    public function down(): void
    {
        if (app()->environment('testing') || config('database.default') !== 'mysql') {
            return;
        }

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropFulltext('name');
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->dropFulltext('name');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropFulltext('body');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropFulltext(['name', 'email']);
        });
    }
};
