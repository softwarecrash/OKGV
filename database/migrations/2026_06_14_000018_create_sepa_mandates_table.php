<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepa_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->string('mandate_reference', 35)->unique();
            $table->text('iban');
            $table->string('iban_last_four', 4);
            $table->text('bic')->nullable();
            $table->text('account_holder');
            $table->date('signed_at');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('mandate_type')->index();
            $table->string('status')->default('active')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_mandates');
    }
};
