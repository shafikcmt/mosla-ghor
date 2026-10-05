<?php

namespace App\Contracts;

use App\Services\CourierProviderConfiguration;

interface CourierConfigurationInterface
{
    public function configuration(): CourierProviderConfiguration;
}
