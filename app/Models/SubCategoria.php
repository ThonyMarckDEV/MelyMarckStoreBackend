<?php

// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoria extends Model
{
    use HasFactory;

    protected $table = 'subcategorias';
    protected $primaryKey = 'idSubCategoria';

    public $timestamps = false;

    protected $fillable = [
        'nombreSubCategoria',
        'estado',
    ];

    // Relación de uno a muchos desde Categoria a Producto
    public function productos()
    {
        return $this->hasMany(Producto::class, 'idCategoria', 'idCategoria');
    }

   // Relationship with Categoria
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'idCategoria', 'idCategoria');
    }

    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

}