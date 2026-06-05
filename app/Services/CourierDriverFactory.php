<?php

namespace App\Services;

use App\Contracts\CourierDriverInterface;
use App\Models\Courier;

/**
 * Resolves the right courier driver for a courier by its slug. This is the ONLY
 * place that maps a courier to its implementation — add new couriers here.
 */
class CourierDriverFactory
{
    public function __construct(
        private SteadfastService $steadfast,
        private ManualCourierService $manual,
        private PathaoService $pathao,
        private SundarbanService $sundarban,
    ) {
    }

    /**
     * Driver for a specific courier (by slug). Unknown slugs → manual driver.
     */
    public function for(Courier $courier): CourierDriverInterface
    {
        return match ($courier->slug) {
            'steadfast' => $this->steadfast,
            'pathao'    => $this->pathao,
            'sundarban' => $this->sundarban,
            default     => $this->manual,
        };
    }

    /** The generic manual driver (used as fallback when a courier's API is off). */
    public function manual(): ManualCourierService
    {
        return $this->manual;
    }
}
