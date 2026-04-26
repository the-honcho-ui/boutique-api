<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'store_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Owner's store (via stores table)
    public function store()
    {
        return $this->hasOne(Store::class);
    }

    // Staff's store (via store_id on users)
    public function staffStore()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    // Returns the store regardless of role
    public function getActiveStore(): ?Store
    {
        if ($this->isOwner()) {
            return $this->store;
        }
        if ($this->isStaff()) {
            return $this->staffStore;
        }
        return null;
    }
}