<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            
            $table->enum('document_type', ['cedula_profesional', 'titulo_profesional', 'licencia']);
            $table->string('document_number', 50)->unique();
            $table->string('document_front_url', 500);
            $table->string('document_back_url', 500)->nullable();
            $table->string('professional_title', 255);
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'resubmit'])->default('pending');
            $table->timestamp('verification_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('attempts')->default(1);
            $table->unsignedInteger('max_attempts')->default(3);
            
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_verifications');
    }
};