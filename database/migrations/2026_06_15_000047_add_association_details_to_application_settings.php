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
            $table->string('association_name')->nullable()->after('system_name');
            $table->string('street')->nullable()->after('association_name');
            $table->string('zip', 20)->nullable()->after('street');
            $table->string('city')->nullable()->after('zip');
            $table->string('contact_name')->nullable()->after('city');
            $table->string('phone', 50)->nullable()->after('contact_name');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('logo_path')->nullable()->after('website');
            $table->string('logo_original_name')->nullable()->after('logo_path');
            $table->string('logo_mime', 100)->nullable()->after('logo_original_name');
            $table->unsignedBigInteger('logo_size')->nullable()->after('logo_mime');
            $table->string('bank_account_holder')->nullable()->after('logo_size');
            $table->string('bank_name')->nullable()->after('bank_account_holder');
            $table->text('bank_iban')->nullable()->after('bank_name');
            $table->string('bank_iban_last_four', 4)->nullable()->after('bank_iban');
            $table->text('bank_bic')->nullable()->after('bank_iban_last_four');
            $table->unsignedSmallInteger('default_payment_term_days')->default(14)->after('bank_bic');
            $table->text('document_footer')->nullable()->after('default_payment_term_days');
            $table->text('email_signature')->nullable()->after('document_footer');
        });

        DB::table('application_settings')->update([
            'association_name' => DB::raw('system_name'),
        ]);
    }

    public function down(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'association_name',
                'street',
                'zip',
                'city',
                'contact_name',
                'phone',
                'email',
                'website',
                'logo_path',
                'logo_original_name',
                'logo_mime',
                'logo_size',
                'bank_account_holder',
                'bank_name',
                'bank_iban',
                'bank_iban_last_four',
                'bank_bic',
                'default_payment_term_days',
                'document_footer',
                'email_signature',
            ], fn (string $column): bool => Schema::hasColumn('application_settings', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
