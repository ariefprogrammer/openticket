<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->unique(); // ID Invoice untuk DOKU
            $table->string('payment_channel')->nullable(); // VA, QRIS, dll
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'expired'])->default('pending');
            $table->text('checkout_url')->nullable(); // Link dari DOKU
            $table->timestamp('doku_payment_datetime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
