<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'store_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'payment_method',
        'status',
        'paystack_reference',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }
}
