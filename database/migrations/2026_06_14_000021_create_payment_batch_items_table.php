<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('sepa_mandate_id')->constrained()->restrictOnDelete();
            $table->string('end_to_end_id', 35)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('sequence_type', 4);
            $table->string('status')->default('pending')->index();
            $table->string('debtor_name', 70);
            $table->text('debtor_iban');
            $table->text('debtor_bic')->nullable();
            $table->string('mandate_reference', 35);
            $table->date('mandate_signed_at');
            $table->string('remittance_information', 140);
            $table->string('return_reason_code', 4)->nullable();
            $table->string('return_reason_text')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_batch_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_batch_items');
    }
};
