<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('street');
            $table->string('zip', 20);
            $table->string('city');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['invoice_id', 'member_id']);
            $table->index(['member_id', 'invoice_id']);
        });

        DB::table('invoices')
            ->join('members', 'members.id', '=', 'invoices.member_id')
            ->select([
                'invoices.id as invoice_id',
                'members.id as member_id',
                'members.member_number',
                'members.first_name',
                'members.last_name',
                'members.street',
                'members.zip',
                'members.city',
            ])
            ->orderBy('invoices.id')
            ->each(function (object $recipient): void {
                DB::table('invoice_recipients')->insert([
                    'invoice_id' => $recipient->invoice_id,
                    'member_id' => $recipient->member_id,
                    'member_number' => $recipient->member_number,
                    'first_name' => $recipient->first_name,
                    'last_name' => $recipient->last_name,
                    'street' => $recipient->street,
                    'zip' => $recipient->zip,
                    'city' => $recipient->city,
                    'is_primary' => true,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_recipients');
    }
};
