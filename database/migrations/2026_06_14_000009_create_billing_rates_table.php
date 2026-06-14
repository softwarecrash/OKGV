<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_type')->index();
            $table->string('scope')->index();
            $table->decimal('amount', 14, 4);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['billing_period_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_rates');
    }
};
