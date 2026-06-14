<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('billing_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('description');
            $table->string('calculation_type');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('total_amount', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
