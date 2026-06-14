<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $table->string('type')->index();
            $table->string('meter_number')->unique();
            $table->date('installed_at');
            $table->date('removed_at')->nullable();
            $table->decimal('start_reading', 14, 4);
            $table->decimal('end_reading', 14, 4)->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['parcel_id', 'type', 'installed_at']);
            $table->index(['parcel_id', 'type', 'removed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
