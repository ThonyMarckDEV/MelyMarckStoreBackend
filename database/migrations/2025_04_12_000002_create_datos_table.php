<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos', function (Blueprint $table) {
            $table->bigIncrements('idDatos');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->boolean('email_verified')->default(0)->comment('0: No, 1: Sí');
            $table->string('dni')->nullable();
            $table->string('ruc')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('google_user')->default(0)->comment('0: No, 1: Sí');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos');
    }
};