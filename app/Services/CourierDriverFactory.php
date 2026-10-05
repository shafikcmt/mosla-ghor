<?php

namespace App\Services;

use App\Contracts\CourierConfigurationInterface;
use App\Contracts\CourierDriverInterface;
use App\Models\Courier;

/**
 * Resolves the right courier driver for a courier by its slug. This is the ONLY
 * resolution path; register provider classes in config/couriers.php.
 */
class CourierDriverFactory
{
    public function __construct(
        private ManualCourierService $manual,
    ) {}

    /**
     * Driver for a specific courier (by slug). Unknown slugs → manual driver.
     */
    public function for(Courier $courier): CourierDriverInterface
    {
        $class = config('couriers.drivers.'.$courier->slug);

        return $class ? app($class) : $this->manual;
    }

    public function configuration(Courier $courier): ?CourierProviderConfiguration
    {
        $driver = $this->for($courier);

        return $driver->supportsApi() && $driver instanceof CourierConfigurationInterface
            ? $driver->configuration() : null;
    }

    /** The generic manual driver (used as fallback when a courier's API is off). */
    public function manual(): ManualCourierService
    {
        return $this->manual;
    }
}
