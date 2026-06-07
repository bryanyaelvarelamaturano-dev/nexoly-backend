<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'document_front_url',
        'document_back_url',
        'professional_title',
        'status',
        'verification_date',
        'rejection_reason',
        'attempts',
        'max_attempts',
        'verified_by',
        'notes'
    ];

    protected $casts = [
        'verification_date' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
    ];

    // Relación: Esta verificación le pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación: Quién fue el administrador que la revisó
    public function administrator()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}