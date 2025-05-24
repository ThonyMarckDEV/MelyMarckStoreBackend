<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_pedidos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CrearTablaPedidos extends Migration
{
    public function up()
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id('idPedido')->unsigned();;
            $table->unsignedBigInteger('idUsuario');
            $table->decimal('total', 10, 2);
            $table->boolean('estado')->default('0')->comment('0: pendiente pago, 1:aprobando pago ,  2: en preparacion, 3: enviado, 4: listo para recoger (estado si es recogo entienda), 5: cancelado ');
            $table->boolean('recojo_local')->default(0)->comment('0: no recojo en local, 1: recojo en local');
            $table->string('departamento');
            $table->string('distrito');
            $table->string('provincia');
            $table->text('direccion_shalom'); 
            $table->timestamp('fecha_pedido')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            // Claves foráneas
            $table->foreign('idUsuario')->references('idUsuario')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pedidos');
    }
}
