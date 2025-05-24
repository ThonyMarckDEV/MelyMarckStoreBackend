<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaDetalleDireccion extends Migration
{
    public function up()
    {
        Schema::create('detalle_direcciones', function (Blueprint $table) {
            $table->id('idDireccion');
            $table->unsignedBigInteger('idUsuario');
            $table->string('departamento');
            $table->string('provincia');
            $table->string('distrito');
            $table->text('direccion_shalom');
            $table->boolean('estado')->comment('0: no usando, 1: usando')->default(1);
            $table->boolean('recojo_local')->default(0)->comment('0: no recojo en local, 1: recojo en local');

            // Definición de la clave foránea
            $table->foreign('idUsuario')
                  ->references('idUsuario')
                  ->on('usuarios')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detalle_direcciones');
    }
}