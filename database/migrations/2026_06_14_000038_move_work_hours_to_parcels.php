<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->decimal('default_work_hours_required', 8, 2)->default(0);
            $table->decimal('default_work_hour_penalty_rate', 10, 2)->default(0);
        });

        Schema::create('work_hours_new', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $table->decimal('hours_required', 8, 2);
            $table->decimal('hours_done', 8, 2);
            $table->decimal('manual_hours_done', 8, 2)->default(0);
            $table->decimal('event_hours_done', 8, 2)->default(0);
            $table->decimal('submission_hours_done', 8, 2)->default(0);
            $table->decimal('hours_missing', 8, 2);
            $table->decimal('penalty_rate', 10, 2);
            $table->decimal('penalty_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['billing_period_id', 'parcel_id']);
            $table->index(['billing_period_id', 'hours_missing']);
        });

        foreach (DB::table('work_hours')->orderBy('id')->get() as $record) {
            $periodEnd = DB::table('billing_periods')
                ->where('id', $record->billing_period_id)
                ->value('ends_at');
            $parcelIds = DB::table('parcel_tenants')
                ->where('member_id', $record->member_id)
                ->whereDate('starts_at', '<=', $periodEnd)
                ->where(function ($query) use ($periodEnd): void {
                    $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $periodEnd);
                })
                ->pluck('parcel_id')
                ->unique()
                ->values();

            if ($parcelIds->count() !== 1) {
                throw new RuntimeException(
                    "Arbeitsstundenkonto {$record->id} kann nicht eindeutig einer Parzelle zugeordnet werden.",
                );
            }

            $duplicate = DB::table('work_hours_new')
                ->where('billing_period_id', $record->billing_period_id)
                ->where('parcel_id', $parcelIds->first())
                ->exists();

            if ($duplicate) {
                throw new RuntimeException(
                    "Mehrere alte Arbeitsstundenkonten betreffen dieselbe Parzelle in Periode {$record->billing_period_id}.",
                );
            }

            DB::table('work_hours_new')->insert([
                'id' => $record->id,
                'billing_period_id' => $record->billing_period_id,
                'parcel_id' => $parcelIds->first(),
                'hours_required' => $record->hours_required,
                'hours_done' => $record->hours_done,
                'manual_hours_done' => $record->manual_hours_done,
                'event_hours_done' => $record->event_hours_done,
                'submission_hours_done' => 0,
                'hours_missing' => $record->hours_missing,
                'penalty_rate' => $record->penalty_rate,
                'penalty_amount' => $record->penalty_amount,
                'notes' => $record->notes,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }

        Schema::drop('work_hours');
        Schema::rename('work_hours_new', 'work_hours');

        Schema::table('work_event_participants', function (Blueprint $table): void {
            $table->foreignId('parcel_id')
                ->nullable()
                ->after('member_id')
                ->constrained()
                ->restrictOnDelete();
            $table->index(['parcel_id', 'status']);
        });

        foreach (DB::table('work_event_participants')->orderBy('id')->get() as $participant) {
            $eventDate = DB::table('work_events')
                ->where('id', $participant->work_event_id)
                ->value('starts_at');
            $parcelIds = DB::table('parcel_tenants')
                ->where('member_id', $participant->member_id)
                ->whereDate('starts_at', '<=', $eventDate)
                ->where(function ($query) use ($eventDate): void {
                    $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $eventDate);
                })
                ->pluck('parcel_id')
                ->unique()
                ->values();

            if ($parcelIds->count() !== 1) {
                throw new RuntimeException(
                    "Arbeitseinsatz-Teilnahme {$participant->id} kann nicht eindeutig einer Parzelle zugeordnet werden.",
                );
            }

            DB::table('work_event_participants')
                ->where('id', $participant->id)
                ->update(['parcel_id' => $parcelIds->first()]);
        }

        Schema::create('work_hour_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->date('worked_at');
            $table->decimal('hours', 8, 2);
            $table->string('description', 1000);
            $table->string('status', 30)->default('pending')->index();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->string('photo_mime', 100)->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();

            $table->index(['parcel_id', 'worked_at']);
            $table->index(['submitted_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_hour_submissions');

        Schema::table('work_event_participants', function (Blueprint $table): void {
            $table->dropIndex(['parcel_id', 'status']);
            $table->dropConstrainedForeignId('parcel_id');
        });

        Schema::create('work_hours_old', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->decimal('hours_required', 8, 2);
            $table->decimal('hours_done', 8, 2);
            $table->decimal('manual_hours_done', 8, 2)->default(0);
            $table->decimal('event_hours_done', 8, 2)->default(0);
            $table->decimal('hours_missing', 8, 2);
            $table->decimal('penalty_rate', 10, 2);
            $table->decimal('penalty_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['billing_period_id', 'member_id']);
            $table->index(['billing_period_id', 'hours_missing']);
        });

        foreach (DB::table('work_hours')->orderBy('id')->get() as $record) {
            $periodEnd = DB::table('billing_periods')
                ->where('id', $record->billing_period_id)
                ->value('ends_at');
            $memberId = DB::table('parcel_tenants')
                ->where('parcel_id', $record->parcel_id)
                ->where('is_primary', true)
                ->whereDate('starts_at', '<=', $periodEnd)
                ->where(function ($query) use ($periodEnd): void {
                    $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $periodEnd);
                })
                ->value('member_id');

            if (! $memberId) {
                throw new RuntimeException(
                    "Parzellenkonto {$record->id} kann nicht zu einem Hauptpächter zurückmigriert werden.",
                );
            }

            DB::table('work_hours_old')->insert([
                'id' => $record->id,
                'billing_period_id' => $record->billing_period_id,
                'member_id' => $memberId,
                'hours_required' => $record->hours_required,
                'hours_done' => $record->hours_done,
                'manual_hours_done' => $record->manual_hours_done,
                'event_hours_done' => $record->event_hours_done,
                'hours_missing' => $record->hours_missing,
                'penalty_rate' => $record->penalty_rate,
                'penalty_amount' => $record->penalty_amount,
                'notes' => $record->notes,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }

        Schema::drop('work_hours');
        Schema::rename('work_hours_old', 'work_hours');

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'default_work_hours_required',
                'default_work_hour_penalty_rate',
            ]);
        });
    }
};
