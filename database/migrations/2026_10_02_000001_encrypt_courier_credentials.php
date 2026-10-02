<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->requireTransactionalTable();
        Schema::table('couriers', function (Blueprint $table) {
            $table->text('api_key')->nullable()->change();
            $table->text('api_secret')->nullable()->change();
        });

        $this->transformCredentials(encrypt: true);
    }

    public function down(): void
    {
        $this->requireTransactionalTable();
        // Coordinate with the old application code while requests/workers are stopped.
        // Rollback restores plaintext storage; it is not a safe application-only rollback.
        $this->transformCredentials(encrypt: false);
        // Keep the widened columns on rollback to avoid truncating existing values.
    }

    private function requireTransactionalTable(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $table = DB::selectOne(
                'SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [DB::connection()->getTablePrefix().'couriers']
            );
            if (strtolower($table->engine ?? '') !== 'innodb') {
                throw new RuntimeException('Courier credential conversion requires an InnoDB table so failures can roll back safely.');
            }
        }
    }

    private function transformCredentials(bool $encrypt): void
    {
        DB::transaction(function () use ($encrypt) {
            DB::table('couriers')->orderBy('id')->chunkById(100, function ($couriers) use ($encrypt) {
                foreach ($couriers as $courier) {
                    $values = [];
                    $secrets = [];
                    foreach (['api_key', 'api_secret'] as $field) {
                        [$plaintext, $alreadyEncrypted] = $this->readCredential($courier->{$field});
                        $value = $encrypt && $plaintext !== null
                            ? ($alreadyEncrypted ? $courier->{$field} : Crypt::encryptString($plaintext))
                            : $plaintext;
                        if ($encrypt && $value !== null && strlen($value) > 65535) {
                            throw new RuntimeException('Encrypted courier credential exceeds the TEXT column capacity.');
                        }
                        if ($value !== $courier->{$field}) {
                            $values[$field] = $value;
                        }
                        if ($plaintext !== null && $plaintext !== '') {
                            $secrets[] = $plaintext;
                        }
                    }
                    if ($encrypt && $secrets) {
                        foreach (['courier_api_last_message', 'courier_api_last_error'] as $field) {
                            if ($courier->{$field} !== null) {
                                $safe = str_replace($secrets, '••••••••', $courier->{$field});
                                if ($safe !== $courier->{$field}) {
                                    $values[$field] = $safe;
                                }
                            }
                        }
                    }
                    if ($values) {
                        DB::table('couriers')->where('id', $courier->id)->update($values);
                    }
                }
            });
        });
    }

    /** @return array{0: ?string, 1: bool} */
    private function readCredential(#[SensitiveParameter] ?string $value): array
    {
        if ($value === null || $value === '') {
            // A raw empty string is not decryptable by an encrypted cast.
            return [null, false];
        }

        $payload = json_decode(base64_decode($value, true) ?: '', true);
        $looksEncrypted = str_starts_with($value, 'eyJpdiI6')
            || (is_array($payload) && array_key_exists('iv', $payload)
                && array_key_exists('value', $payload) && array_key_exists('mac', $payload));
        if ($looksEncrypted) {
            try {
                return [Crypt::decryptString($value), true];
            } catch (DecryptException) {
                // Never treat ciphertext encrypted with a missing/wrong key as plaintext.
                throw new RuntimeException('Courier credentials could not be decrypted. Restore the existing APP_KEY before retrying.');
            }
        }

        return [$value, false];
    }
};
