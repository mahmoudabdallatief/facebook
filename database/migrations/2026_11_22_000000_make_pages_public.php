<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Facebook Pages are public identities. Privacy settings belong to Groups.
        DB::table('pages')->where('type', 'page')->update(['privacy' => 'public']);
    }

    public function down(): void
    {
        // Existing page privacy values cannot be reconstructed safely.
    }
};
