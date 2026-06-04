<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Validation rule rejecting values that look like a login email address.
     *
     * Real courier API keys/secrets are never plain emails, so an email-shaped
     * value almost always means browser autofill injected the admin login.
     */
    protected function notLoginEmailRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (filled($value) && preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', trim((string) $value))) {
                $fail('API Key হিসেবে login email save করা যাবে না। Browser autofill বন্ধ করে সঠিক API credential দিন।');
            }
        };
    }
}
