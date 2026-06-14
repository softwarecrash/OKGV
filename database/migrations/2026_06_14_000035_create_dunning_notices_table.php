<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_notices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('notice_number')->unique();
            $table->unsignedTinyInteger('level');
            $table->string('status')->default('issued')->index();
            $table->string('invoice_number');
            $table->date('issued_at');
            $table->date('due_at');
            $table->decimal('invoice_amount', 14, 2);
            $table->decimal('fee_amount', 14, 2)->default(0);
            $table->decimal('previous_fees_amount', 14, 2)->default(0);
            $table->decimal('total_due', 14, 2);
            $table->json('recipients');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'level']);
            $table->index(['invoice_id', 'status']);
            $table->index(['due_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_notices');
    }
};
