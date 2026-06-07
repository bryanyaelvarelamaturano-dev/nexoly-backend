<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'price',
        'status',
        'payment_status',
        'payment_method'
    ];

    // --- RELACIONES ---

    /**
     * El cliente que compra o contrata el servicio.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * El servicio que se está contratando.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Los intentos y registros de transacciones financieras asociados a este contrato.
     */
    public function paymentTransactions()
    {
        return $this->hasMany(\App\Models\PaymentTransaction::class);
    }
}