<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepa_settings', function (Blueprint $table) {
            $table->id();
            $table->string('creditor_name');
            $table->string('creditor_identifier', 35)->unique();
            $table->text('iban');
            $table->string('iban_last_four', 4);
            $table->text('bic')->nullable();
            $table->boolean('batch_booking')->default(true);
            $table->string('message_version')->default('pain.008.001.08');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_settings');
    }
};
