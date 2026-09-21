<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Turns a guest wholesale enquiry into a real, trackable account.
 *
 * A guest who submits an enquiry gets a User (role=customer) + Customer created
 * on the spot, keyed by phone. The password is an unguessable random value —
 * the guest later "claims" the account through a signed set-password link
 * (emailed automatically, or shared manually over WhatsApp). We NEVER overwrite
 * the password of an existing account.
 */
class GuestWholesaleAccount
{
    /**
     * Find the customer account behind a phone, or create one. Returns the
     * Customer, whether the login account was newly created, and — only for a
     * brand-new account — a signed 7-day set-password URL.
     *
     * @return array{customer: Customer, isNew: bool, setPasswordUrl: ?string}
     */
    public static function findOrCreate(string $name, string $phone, ?string $email = null): array
    {
        $normalized = Phone::normalize($phone) ?: trim($phone);
        $email      = $email ? mb_strtolower(trim($email)) : null;

        $user  = User::where('role', 'customer')->where('phone', $normalized)->first();
        $isNew = false;

        if (! $user) {
            $isNew = true;
            // Only attach the email if it is not already taken by another login.
            $emailForUser = ($email && ! User::where('email', $email)->exists()) ? $email : null;

            $user = User::create([
                'name'     => $name,
                'email'    => $emailForUser,
                'phone'    => $normalized,
                'password' => Hash::make(Str::random(40)), // unguessable; claimed via set-password link
                'role'     => 'customer',
                'is_admin' => false,
            ]);
        }

        // Reuse an existing CRM record (past orders) or create a fresh profile.
        $customer = Customer::firstOrCreate(
            ['mobile_number' => $normalized],
            ['name' => $name, 'email' => $email, 'is_active' => true]
        );

        return [
            'customer'       => $customer,
            'isNew'          => $isNew,
            'setPasswordUrl' => $isNew ? self::setPasswordUrlFor($user) : null,
        ];
    }

    /** A fresh signed, 7-day set-password link for a customer's login account. */
    public static function setPasswordUrlFor(User $user): string
    {
        return URL::temporarySignedRoute(
            'customer.set-password.show',
            now()->addDays(7),
            ['user' => $user->id]
        );
    }
}
