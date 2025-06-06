<?php

// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';
    protected $primaryKey = 'idCategoria';

    public $timestamps = false;

    protected $fillable = [
        'nombreCategoria',
        'imagen',
        'estado',
    ];

    // Relación de uno a muchos desde Categoria a Producto
    public function productos()
    {
        return $this->hasMany(Producto::class, 'idCategoria', 'idCategoria');
    }

    // Relationship with SubCategoria
    public function subcategorias()
    {
        return $this->hasMany(SubCategoria::class, 'idCategoria', 'idCategoria');
    }

    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

}