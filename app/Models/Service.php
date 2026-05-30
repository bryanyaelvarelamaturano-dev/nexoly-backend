<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'category',
        'modality',
        'image_url',
        'active',
        'user_id'
    ];

    // Esto hace que cada vez que pidas un servicio, se incluya el score de confianza automáticamente
    protected $appends = ['reliability_score'];

    /**
     * Lógica de la Barra de Confiabilidad
     * Calcula un porcentaje basado en el promedio de estrellas y atributos positivos.
     */
    public function getReliabilityScoreAttribute()
{
    if (!$this->relationLoaded('reviews') || $this->reviews->isEmpty()) {
        return 100;
    }

    try {
        $reviews = $this->reviews;
        $total = $reviews->count();

        $avgStars = $reviews->avg('rating') ?? 0;
        $starScore = ($avgStars / 5) * 70;

        $positiveReviews = $reviews->filter(function ($review) {
            $attrs = $review->getAttribute('attributes');

            if (is_string($attrs)) {
                $attrs = json_decode($attrs, true);
            }

            return is_array($attrs) && count($attrs) > 0;
        })->count();

        $attributeScore = ($positiveReviews / $total) * 30;

        return (int) round($starScore + $attributeScore);
    } catch (\Throwable $e) {
        return 100;
    }
}


    /**
     * Relación con el Usuario (Dueño del servicio)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con las Reseñas
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class);
    }

    /**
     * Promedio de Rating
     */
    public function averageRating()
    {
        return $this->reviews()->avg('rating');
    }
}