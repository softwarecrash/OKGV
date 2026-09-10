<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_follow_ups', function (Blueprint $table): void {
            $table->unsignedBigInteger('board_resolution_id')->nullable()->change();
            $table->foreignId('member_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('remind_at')->nullable()->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->string('recurrence', 20)->default('none');
            $table->date('recurrence_anchor')->nullable();
            $table->unsignedInteger('occurrence')->default(0);
            $table->foreignId('previous_task_id')->nullable()->unique()->constrained('board_follow_ups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $extended = DB::table('board_follow_ups')->whereNull('board_resolution_id')->orWhere('recurrence', '!=', 'none');
        foreach (['member_id', 'parcel_id', 'document_id', 'remind_at', 'started_at', 'cancelled_at', 'archived_at', 'recurrence_anchor', 'previous_task_id'] as $field) {
            $extended->orWhereNotNull($field);
        }
        if ($extended->exists()) {
            throw new RuntimeException('Rollback would lose task data. Restore a compatible backup instead.');
        }
        Schema::table('board_follow_ups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_id');
            $table->dropConstrainedForeignId('parcel_id');
            $table->dropConstrainedForeignId('document_id');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropUnique(['previous_task_id']);
            $table->dropConstrainedForeignId('previous_task_id');
            $table->dropIndex(['remind_at']);
            $table->dropColumn(['remind_at', 'started_at', 'cancelled_at', 'cancel_reason', 'archived_at', 'recurrence', 'recurrence_anchor', 'occurrence']);
            $table->unsignedBigInteger('board_resolution_id')->nullable(false)->change();
        });
    }
};
