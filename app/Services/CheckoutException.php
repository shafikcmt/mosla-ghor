<?php

namespace App\Services;

/**
 * Carries a user-friendly Bangla message + the form field it relates to, so
 * controllers can render it as a JSON 422 (modal) or a flash redirect (pages).
 */
class CheckoutException extends \RuntimeException
{
    public function __construct(string $message, public string $field = 'items')
    {
        parent::__construct($message);
    }
}
