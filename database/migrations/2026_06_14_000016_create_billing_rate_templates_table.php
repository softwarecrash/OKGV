<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_rate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('calculation_type')->index();
            $table->string('scope')->index();
            $table->decimal('default_amount', 14, 4)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('billing_rates', function (Blueprint $table) {
            $table->foreignId('billing_rate_template_id')
                ->nullable()
                ->after('billing_period_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billing_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_rate_template_id');
        });

        Schema::dropIfExists('billing_rate_templates');
    }
};
