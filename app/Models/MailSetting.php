<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'driver', 'host', 'port', 'username', 'password',
        'encryption', 'from_address', 'from_name', 'is_enabled',
    ];

    protected $casts = [
        // Password is encrypted at rest — stronger than the courier api_key/api_secret
        // (which are plaintext + hidden). Laravel transparently encrypts on write
        // and decrypts on read; the ciphertext never appears in the DB or logs.
        'password'   => 'encrypted',
        'port'       => 'integer',
        'is_enabled' => 'boolean',
    ];

    /**
     * Never leak the SMTP password when the model is serialized.
     */
    protected $hidden = ['password'];

    /**
     * Single-row settings. firstOrCreate (not firstOrNew) so update() persists
     * reliably — same rule the other settings models follow.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'driver'     => 'smtp',
            'is_enabled' => false,
        ]);
    }

    /** Are DB-managed SMTP settings ready to use for real sending? */
    public function isUsable(): bool
    {
        return $this->is_enabled
            && $this->driver === 'smtp'
            && ! empty($this->host)
            && ! empty($this->port);
    }

    /** Masked preview of the stored password — never the full value. */
    public function maskedPassword(): string
    {
        $value = (string) ($this->password ?? '');
        if ($value === '') {
            return '';
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', max(4, $len - 4)) . substr($value, -4);
    }
}
