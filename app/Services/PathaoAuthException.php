<?php

namespace App\Services;

/** Raised when Pathao's issue-token endpoint does not return an access token. */
class PathaoAuthException extends \RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct('Pathao issue-token failed (HTTP ' . $status . ')');
    }
}
