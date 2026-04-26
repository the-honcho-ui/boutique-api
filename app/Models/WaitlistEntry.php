<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'store_id',
        'product_variant_id',
        'email',
        'status',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
