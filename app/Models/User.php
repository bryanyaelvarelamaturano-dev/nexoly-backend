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
        'country',
        'state',
        'city',
        'business_name',
        'google_id',
        'avatar',
        // --- NUEVOS CAMPOS AGREGADOS PARA KYC Y PAYMENTS ---
        'is_verified',
        'verification_tier',
        'provider_status',
        'bank_account_holder',
        'bank_account_number',
        'bank_routing_number',
        'bank_country',
        'verified_at',
        'last_verification_check',
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
            // --- NUEVOS CASTS DE FECHAS ---
            'verified_at' => 'datetime',
            'last_verification_check' => 'datetime',
            'is_verified' => 'boolean',
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

    // --- MÉTODOS DE LÓGICA DE NEGOCIO ---

    /**
     * Determina si el usuario es un proveedor verificado y activo.
     */
    public function isVerifiedProvider(): bool
    {
        return $this->is_verified && $this->verification_tier === 'verified' && $this->provider_status === 'active';
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

    /**
     * Un usuario tiene un expediente de verificación profesional (KYC)
     */
    public function professionalVerification()
    {
        return $this->hasOne(\App\Models\ProfessionalVerification::class, 'user_id');
    }

    /**
     * Un usuario puede tener múltiples tokens o métodos de pago registrados en Stripe
     */
    public function stripeTokens()
    {
        return $this->hasMany(\App\Models\StripeCustomerToken::class);
    }
}