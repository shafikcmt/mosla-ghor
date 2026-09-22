<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        // Backfill a missing email onto REUSED accounts — an existing login User
        // found by phone, and/or an existing Customer row created elsewhere
        // without an email (e.g. OrderController::upsertCustomer runs first and
        // makes the Customer with no email). We only ever FILL an empty email; we
        // NEVER overwrite an email the account already has, so one order's typed
        // value can't silently replace a customer's contact info. The new-account
        // creation path already sets the email on create and is left untouched.
        if ($email) {
            try {
                // Reused login account with no email yet → adopt this one, unless
                // another account already owns that email (respect uniqueness).
                if (! $isNew
                    && empty($user->email)
                    && ! User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                    $user->update(['email' => $email]);
                }

                // Existing (found, not just-created) CRM record with no email →
                // adopt. customers.email has no unique index today, but we stay
                // symmetric with the User guard and future-proof one: only adopt
                // when no OTHER customer already holds this email.
                if (! $customer->wasRecentlyCreated
                    && empty($customer->email)
                    && ! Customer::where('email', $email)->where('id', '!=', $customer->id)->exists()) {
                    $customer->update(['email' => $email]);
                }
            } catch (\Throwable $e) {
                // A rare collision (or any DB error) must never crash order/enquiry
                // placement — log a warning and skip the backfill silently.
                Log::warning('GuestWholesaleAccount email backfill skipped: ' . $e->getMessage());
            }
        }

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
