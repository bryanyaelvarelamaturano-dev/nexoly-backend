<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'service_id',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'payment_provider',
        'transaction_id',
        'payment_intent_id',
        'status',
        'failure_reason',
        'webhook_received',
        'webhook_timestamp'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'webhook_received' => 'boolean',
        'webhook_timestamp' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}