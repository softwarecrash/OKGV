<?php

use App\Enums\UserPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->json('permissions');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::table('permission_profiles')->insert([
            'name' => 'Vorstand Standard',
            'description' => 'Zurückhaltende Grundrechte ohne Zugriff auf Abrechnung oder SEPA.',
            'permissions' => json_encode([
                UserPermission::ViewAllMasterData->value,
                UserPermission::ManageMasterData->value,
                UserPermission::ViewAllMeters->value,
                UserPermission::ReviewTenantRegistrations->value,
                UserPermission::ReviewMeterReadingSubmissions->value,
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_profiles');
    }
};
