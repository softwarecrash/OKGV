<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropForeign(['parcel_id']);
            $table->foreignId('parcel_id')->nullable()->change();
            $table->string('parcel_number')->nullable()->change();
            $table->foreign('parcel_id')->references('id')->on('parcels')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table): void {
            $table->dropForeign(['parcel_id']);
            $table->foreignId('parcel_id')->nullable(false)->change();
            $table->string('parcel_number')->nullable(false)->change();
            $table->foreign('parcel_id')->references('id')->on('parcels')->restrictOnDelete();
        });
    }
};
