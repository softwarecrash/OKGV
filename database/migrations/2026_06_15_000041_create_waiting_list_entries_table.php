<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->date('applied_at');
            $table->unsignedTinyInteger('priority')->default(3);
            $table->string('status')->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'applied_at']);
            $table->index(['last_name', 'first_name']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list_entries');
    }
};
