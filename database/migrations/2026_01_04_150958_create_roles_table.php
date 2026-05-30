<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- No olvides agregar esta línea

return new class extends Migration
{
    public function up(): void
    {
        // Solo la crea si no existe (por seguridad)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // Insertamos los 3 roles necesarios
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'Cliente'],
            ['id' => 2, 'name' => 'Proveedor'],
            ['id' => 3, 'name' => 'Admin'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};