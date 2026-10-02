<?php

namespace App\Console\Commands;

use App\Models\Courier;
use Illuminate\Console\Command;

class CleanCourierCredentials extends Command
{
    /**
     * Usage:
     *   php artisan courier:clean-credentials          (dry-run, lists what would change)
     *   php artisan courier:clean-credentials --force   (clears every reported candidate)
     *
     * Email shape does NOT prove a key is invalid. Never use --force as deployment
     * cleanup. Verify every candidate with the provider and back up credentials first.
     */
    protected $signature = 'courier:clean-credentials {--force : Apply the changes instead of a dry-run}';

    protected $description = 'Report email-shaped courier API keys. --force clears both credentials and disables API for every candidate; shape does not prove invalidity.';

    public function handle(): int
    {
        // This is only an autofill heuristic, not provider credential validation.
        $emailLike = '/^[^@\s]+@[^@\s]+\.[^@\s]+$/';

        $bad = Courier::all()->filter(function (Courier $c) use ($emailLike) {
            return filled($c->api_key) && preg_match($emailLike, trim((string) $c->api_key));
        });

        if ($bad->isEmpty()) {
            $this->info('No email-shaped courier API keys found. Nothing to report.');
            return self::SUCCESS;
        }

        $this->warn('Email-shaped API keys requiring manual verification (not proven invalid):');
        foreach ($bad as $c) {
            $this->line(sprintf('  • #%d %s (slug: %s) → api_key="%s"', $c->id, $c->name, $c->slug, '••••••••'));
        }

        if (! $this->option('force')) {
            $this->newLine();
            $this->comment('Dry-run only. Do not use --force unless every candidate is independently verified invalid and backed up.');
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
