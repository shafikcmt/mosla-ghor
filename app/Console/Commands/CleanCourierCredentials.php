<?php

namespace App\Console\Commands;

use App\Models\Courier;
use Illuminate\Console\Command;

class CleanCourierCredentials extends Command
{
    /**
     * Usage:
     *   php artisan courier:clean-credentials          (dry-run, lists what would change)
     *   php artisan courier:clean-credentials --force   (actually clears bad credentials)
     */
    protected $signature = 'courier:clean-credentials {--force : Apply the changes instead of a dry-run}';

    protected $description = 'Clear only WRONG courier API credentials (e.g. login email saved via browser autofill). Real keys and courier rows are never deleted.';

    public function handle(): int
    {
        // A credential is "wrong" when api_key looks like an email address
        // (real Steadfast/Pathao keys never do). Steadfast's genuine key is
        // a random token and will not match — so it is left untouched.
        $emailLike = '/^[^@\s]+@[^@\s]+\.[^@\s]+$/';

        $bad = Courier::all()->filter(function (Courier $c) use ($emailLike) {
            return filled($c->api_key) && preg_match($emailLike, trim((string) $c->api_key));
        });

        if ($bad->isEmpty()) {
            $this->info('No bad courier credentials found. Nothing to clean.');
            return self::SUCCESS;
        }

        $this->warn('Couriers with email-shaped (wrong) API keys:');
        foreach ($bad as $c) {
            $this->line(sprintf('  • #%d %s (slug: %s) → api_key="%s"', $c->id, $c->name, $c->slug, $c->api_key));
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry-run only. Re-run with --force to clear these credentials.');
            return self::SUCCESS;
        }

        foreach ($bad as $c) {
            // Null only the credentials + disable API. Row, base_url, notes, status all kept.
            $c->forceFill([
                'api_key'     => null,
                'api_secret'  => null,
                'api_enabled' => false,
            ])->save();
            $this->info(sprintf('Cleared credentials for #%d %s.', $c->id, $c->name));
        }

        $this->newLine();
        $this->info('Done. Re-enter the correct API credentials from the admin courier settings page.');
        return self::SUCCESS;
    }
}
