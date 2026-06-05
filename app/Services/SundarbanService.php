<?php

namespace App\Services;

/**
 * Sundarban courier driver — MANUAL placeholder.
 *
 * No API integration yet; behaves as a manual courier. To add a real API later:
 *   1. Override supportsApi() to return true.
 *   2. Implement createParcel()/testConnection().
 *   3. Add 'sundarban' to Courier::API_SUPPORTED_SLUGS.
 * Admin/vendor flow is untouched — resolution happens in CourierDriverFactory.
 */
class SundarbanService extends ManualCourierService
{
}
