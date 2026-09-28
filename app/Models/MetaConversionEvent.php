<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MetaConversionEvent extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['payload'];

    protected $casts = [
        'payload' => 'encrypted:array',
        'event_time' => 'integer',
        'attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'queued_until' => 'datetime',
        'locked_until' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function scopeDue(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->where(function (Builder $query) {
                $query->whereIn('status', ['pending', 'retry'])
                    ->where(fn (Builder $q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()));
            })->orWhere(function (Builder $query) {
                $query->where('status', 'sending')->where('locked_until', '<=', now());
            });
        });
    }

    public function scopeReadyToDispatch(Builder $query): void
    {
        $query->due()->where(fn (Builder $q) => $q->whereNull('queued_until')->orWhere('queued_until', '<=', now()));
    }
}
