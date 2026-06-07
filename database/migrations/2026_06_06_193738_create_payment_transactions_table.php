<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('MXN'); // Cambiado a MXN para el mercado mexicano
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->nullable();
            
            $table->enum('payment_provider', ['stripe', 'paypal', 'manual']);
            $table->string('transaction_id', 255)->unique()->nullable();
            $table->string('payment_intent_id', 255)->nullable();
            
            $table->enum('status', ['pending', 'success', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();
            
            $table->boolean('webhook_received')->default(false);
            $table->timestamp('webhook_timestamp')->nullable();
            
            $table->timestamps();
            
            $table->index('contract_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};