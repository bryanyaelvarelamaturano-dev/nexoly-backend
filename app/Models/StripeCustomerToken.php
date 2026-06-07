<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCustomerToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'card_last_four',
        'card_brand',
        'card_expiry_month',
        'card_expiry_year',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'card_expiry_month' => 'integer',
        'card_expiry_year' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}