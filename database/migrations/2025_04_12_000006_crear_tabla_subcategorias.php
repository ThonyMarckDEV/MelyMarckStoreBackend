<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_categorias_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaSubCategorias extends Migration
{
    public function up()
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->bigIncrements('idSubCategoria'); // Clave primaria con bigIncrements (unsignedBigInteger)
            $table->string('nombreSubCategoria');
            $table->boolean('estado')->default(true); // Estado por defecto a true

            // Clave foránea
            $table->unsignedBigInteger('idCategoria');
            $table->foreign('idCategoria')->references('idCategoria')->on('categorias')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subcategorias');
    }
}
