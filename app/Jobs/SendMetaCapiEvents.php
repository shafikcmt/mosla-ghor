<?php

namespace App\Jobs;

use App\Support\MetaCapi;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Sends Conversions API events. Dispatched with dispatchAfterResponse(), so it runs
 * in the same PHP process right after the customer's response — no queue worker
 * needed, and the token is read from the DB at send time (never serialised).
 */
class SendMetaCapiEvents
{
    use Dispatchable;

    public function __construct(public array $events)
    {
    }

    public function handle(): void
    {
        MetaCapi::send($this->events);
    }
}
