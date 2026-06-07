<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_suspended');
            $table->enum('verification_tier', ['none', 'pending', 'verified', 'rejected'])->default('none')->after('is_verified');
            $table->enum('provider_status', ['inactive', 'active', 'suspended', 'blocked'])->default('inactive')->after('verification_tier');
            
            $table->string('bank_account_holder', 255)->nullable()->after('provider_status');
            $table->string('bank_account_number', 50)->nullable()->after('bank_account_holder');
            $table->string('bank_routing_number', 50)->nullable()->after('bank_account_number');
            $table->string('bank_country', 2)->nullable()->after('bank_routing_number');
            
            $table->timestamp('verified_at')->nullable()->after('bank_country');
            $table->timestamp('last_verification_check')->nullable()->after('verified_at');
            
            $table->index('is_verified');
            $table->index('verification_tier');
            $table->index('provider_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_verified', 'verification_tier', 'provider_status',
                'bank_account_holder', 'bank_account_number', 'bank_routing_number',
                'bank_country', 'verified_at', 'last_verification_check'
            ]);
        });
    }
};