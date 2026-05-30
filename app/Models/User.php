<?php

namespace App\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Estos campos son los que Laravel permite guardar en la DB.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_image',
        'role_id',
        'is_suspended',
        // --- NUEVOS CAMPOS AGREGADOS ---
        'country',
        'state',
        'city',
        'business_name',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- MÉTODOS OBLIGATORIOS PARA JWT ---

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        /**
         * Puedes agregar datos extra al token aquí si quieres, 
         * por ahora lo dejamos vacío.
         */
        return [];
    }

    // --- RELACIONES ---

    /**
     * Un usuario puede tener muchos servicios (como proveedor)
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Un usuario puede dejar muchas reseñas
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class);
    }

    /**
     * Mensajes enviados por el usuario
     */
    public function sentMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'sender_id');
    }

    /**
     * Mensajes recibidos por el usuario
     */
    public function receivedMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'receiver_id');
    }
}