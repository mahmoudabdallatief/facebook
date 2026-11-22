<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the stricter membership boundary when removing the old "closed" option.
        DB::table('groups')->where('privacy', 'closed')->update(['privacy' => 'private']);
    }

    public function down(): void
    {
        // Private groups may have been created after this migration, so do not
        // convert all private groups back to the removed state.
    }
};
