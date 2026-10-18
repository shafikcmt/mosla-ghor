<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'password_set_at',
        'is_admin',
        'role',
        'google_id',
        'facebook_id',
        'avatar_url',
        'staff_vendor_id',
        'staff_permissions',
        'staff_active',
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
            'password'          => 'hashed',
            'password_set_at'   => 'datetime',
            'is_admin'          => 'boolean',
            'staff_permissions' => 'array',
            'staff_active'      => 'boolean',
        ];
    }

    /**
     * The shop this user works in: their own vendor, or — for shop staff — the
     * owner's vendor, so every `->vendor` lookup scopes staff to that shop.
     */
    public function vendor(): HasOne|\Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->staff_vendor_id
            ? $this->belongsTo(Vendor::class, 'staff_vendor_id')
            : $this->hasOne(Vendor::class);
    }

    /** Shop staff (দোকানের খাতা) — not the shop owner. */
    public function isStaff(): bool
    {
        return $this->staff_vendor_id !== null;
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'mobile_number', 'phone');
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    /** True once the customer has claimed the account by setting their own password. */
    public function hasSetPassword(): bool
    {
        return ! is_null($this->password_set_at);
    }

    /**
     * Mail routing for notifications. Laravel calls this on the NOTIFIABLE (the
     * User), not the notification — so a notification that needs to reach a
     * captured contact address (e.g. a guest enquiry email, when the User's own
     * email is empty) exposes mailRouteOverride() and we prefer it here.
     */
    public function routeNotificationForMail($notification = null)
    {
        if ($notification !== null && method_exists($notification, 'mailRouteOverride')) {
            $override = $notification->mailRouteOverride();
            if (! empty($override)) {
                return $override;
            }
        }

        return $this->email;
    }

    /**
     * Super admins have elevated privileges such as permanently (hard) deleting
     * orders from Trash. Regular admins can trash/restore but not force-delete.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->role === 'super_admin';
    }
}
