<?php

use App\Enums\NumberSequenceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->string('format', 100);
            $table->unsignedTinyInteger('padding')->default(5);
            $table->unsignedBigInteger('next_value')->default(1);
            $table->boolean('reset_yearly')->default(false);
            $table->unsignedSmallInteger('last_year')->nullable();
            $table->timestamps();
        });

        foreach (NumberSequenceType::cases() as $type) {
            DB::table('number_sequences')->insert([
                'type' => $type->value,
                'format' => $type->defaultFormat(),
                'padding' => $type->defaultPadding(),
                'next_value' => 1,
                'reset_yearly' => $type->resetsYearlyByDefault(),
                'last_year' => $type->resetsYearlyByDefault() ? (int) date('Y') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
