<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('parcels')->whereNull('use_type')->update(['use_type' => 'lease']);
    }

    public function down(): void
    {
        // Existing parcel types are business data and must not be removed on rollback.
    }
};
