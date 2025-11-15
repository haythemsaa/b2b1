<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relations
    public function vendorProfile()
    {
        return $this->hasOne(VendorProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class, 'vendor_id');
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class, 'vendor_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    // Scopes
    public function scopeVendors($query)
    {
        return $query->where('role', 'vendor');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Helper Methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function hasFeature(string $feature): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedFeatures = $this->vendorProfile?->merged_features ?? [];
        return in_array($feature, $allowedFeatures);
    }
}
