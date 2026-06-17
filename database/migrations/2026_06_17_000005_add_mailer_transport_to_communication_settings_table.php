<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_settings', function (Blueprint $table): void {
            $table->string('mailer_transport', 20)->default('smtp')->after('smtp_enabled');
            $table->string('sendmail_path')->nullable()->after('smtp_password');
            $table->string('smtp_host')->nullable()->change();
            $table->unsignedSmallInteger('smtp_port')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('communication_settings', function (Blueprint $table): void {
            $table->string('smtp_host')->default('127.0.0.1')->nullable(false)->change();
            $table->unsignedSmallInteger('smtp_port')->default(587)->nullable(false)->change();
        });

        Schema::table('communication_settings', function (Blueprint $table): void {
            $table->dropColumn(['mailer_transport', 'sendmail_path']);
        });
    }
};
