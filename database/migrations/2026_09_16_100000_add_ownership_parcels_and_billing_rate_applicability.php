<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table): void {
            $table->string('use_type')->default('lease')->index()->after('status');
            $table->boolean('has_operating_permit')->default(false)->after('use_type');
        });

        Schema::table('billing_rate_templates', function (Blueprint $table): void {
            $table->boolean('applies_to_leased_parcels')->default(true)->after('scope');
            $table->boolean('applies_to_owned_parcels')->default(false)->after('applies_to_leased_parcels');
        });

        Schema::table('billing_rates', function (Blueprint $table): void {
            $table->boolean('applies_to_leased_parcels')->default(true)->after('scope');
            $table->boolean('applies_to_owned_parcels')->default(false)->after('applies_to_leased_parcels');
        });
    }

    public function down(): void
    {
        Schema::table('billing_rates', function (Blueprint $table): void {
            $table->dropColumn(['applies_to_leased_parcels', 'applies_to_owned_parcels']);
        });

        Schema::table('billing_rate_templates', function (Blueprint $table): void {
            $table->dropColumn(['applies_to_leased_parcels', 'applies_to_owned_parcels']);
        });

        Schema::table('parcels', function (Blueprint $table): void {
            $table->dropColumn(['use_type', 'has_operating_permit']);
        });
    }
};
