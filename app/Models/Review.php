<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // Añadimos 'attributes' para permitir el guardado masivo
    protected $fillable = [
        'user_id', 
        'service_id', 
        'rating', 
        'comment', 
        'attributes'
    ];

    /**
     * Casting de atributos.
     * Esto convierte automáticamente el JSON de la base de datos 
     * en un array de PHP al leerlo, y viceversa al guardar.
     */
    protected $casts = [
        'attributes' => 'array',
        'rating' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}