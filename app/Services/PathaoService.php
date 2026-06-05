<?php

namespace App\Services;

/**
 * Pathao courier driver — MANUAL placeholder.
 *
 * Pathao has no API integration yet, so it behaves as a manual courier
 * (inherits ManualCourierService). When the real Pathao API is implemented:
 *   1. Override supportsApi() to return true.
 *   2. Implement createParcel()/testConnection() against Pathao's API.
 *   3. Add 'pathao' to Courier::API_SUPPORTED_SLUGS.
 * No admin/vendor flow changes are needed — CourierService resolves this driver
 * via CourierDriverFactory by slug.
 */
class PathaoService extends ManualCourierService
{
}
