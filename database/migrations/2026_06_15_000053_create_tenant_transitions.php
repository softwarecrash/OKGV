<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $table->foreignId('outgoing_primary_tenancy_id')->constrained('parcel_tenants')->restrictOnDelete();
            $table->foreignId('incoming_primary_tenancy_id')->constrained('parcel_tenants')->restrictOnDelete();
            $table->date('transfer_date');
            $table->json('outgoing_members_snapshot');
            $table->json('incoming_members_snapshot');
            $table->json('open_claims_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('completed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['parcel_id', 'transfer_date']);
        });

        Schema::create('tenant_transition_meter_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_transition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meter_id')->constrained()->restrictOnDelete();
            $table->foreignId('meter_reading_id')->constrained()->restrictOnDelete();
            $table->decimal('reading_value', 14, 4);
            $table->timestamps();

            $table->unique(['tenant_transition_id', 'meter_id'], 'transition_meter_unique');
            $table->unique('meter_reading_id');
        });

        Schema::create('tenant_transition_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_transition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->string('category', 20);
            $table->timestamps();

            $table->unique('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_transition_documents');
        Schema::dropIfExists('tenant_transition_meter_readings');
        Schema::dropIfExists('tenant_transitions');
    }
};
