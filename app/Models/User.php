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
        'is_admin',
        'role',
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
            'is_admin'          => 'boolean',
        ];
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class, 'mobile_number', 'phone');
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
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
