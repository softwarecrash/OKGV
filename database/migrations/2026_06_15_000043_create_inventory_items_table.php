<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('inventory_number')->unique();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->string('location')->nullable();
            $table->date('purchased_at')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
