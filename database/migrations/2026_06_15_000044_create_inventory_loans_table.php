<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('member_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('borrower_name');
            $table->date('issued_at');
            $table->date('due_at')->nullable()->index();
            $table->date('returned_at')->nullable()->index();
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('returned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('condition_on_issue')->nullable();
            $table->text('condition_on_return')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_loans');
    }
};
