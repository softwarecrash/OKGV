<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_rate_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_rate_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'billing_rate_id']);
            $table->index(['parcel_id', 'billing_rate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_rate_assignments');
    }
};
