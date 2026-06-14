<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 35)->unique();
            $table->date('requested_collection_date');
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('control_sum', 14, 2)->default(0);
            $table->string('creditor_name', 70);
            $table->string('creditor_identifier', 35);
            $table->text('creditor_iban');
            $table->text('creditor_bic')->nullable();
            $table->boolean('batch_booking')->default(true);
            $table->string('message_version')->default('pain.008.001.08');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('exported_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('xml_sha256', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_batches');
    }
};
