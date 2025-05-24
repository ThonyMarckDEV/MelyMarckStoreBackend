<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_productos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaProductos extends Migration
{
    public function up()
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->bigIncrements('idProducto');
            $table->string('nombreProducto');
            $table->string('descripcion', 60)->nullable();
            $table->decimal('precio', 8, 2);
            // Nueva columna 'estado' de tipo string con valor predeterminado 'activo'
            $table->boolean('estado')->default(true);
            $table->timestamps();

            // Clave foránea
            $table->unsignedBigInteger('idSubCategoria');
            $table->foreign('idSubCategoria')->references('idSubCategoria')->on('subcategorias')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('productos');
    }
}