<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_customer_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('stripe_customer_id', 255)->unique()->nullable();
            $table->string('stripe_payment_method_id', 255)->nullable();
            
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->unsignedInteger('card_expiry_month')->nullable();
            $table->unsignedInteger('card_expiry_year')->nullable();
            
            $table->boolean('is_default')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_customer_tokens');
    }
};