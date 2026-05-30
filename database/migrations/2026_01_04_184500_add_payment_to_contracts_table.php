<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('contracts', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_status');
            }
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('contracts', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
