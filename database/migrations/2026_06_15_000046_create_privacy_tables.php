<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_privacy_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('share_name')->default(false);
            $table->boolean('share_email')->default(false);
            $table->boolean('share_phone')->default(false);
            $table->boolean('share_mobile')->default(false);
            $table->boolean('share_address')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
        });

        Schema::create('privacy_erasure_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamp('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->json('blockers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_erasure_requests');
        Schema::dropIfExists('member_privacy_settings');
    }
};
