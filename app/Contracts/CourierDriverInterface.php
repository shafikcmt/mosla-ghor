<?php

namespace App\Contracts;

use App\Models\Courier;

/**
 * Contract every courier driver implements. The common CourierService talks to
 * couriers ONLY through this interface, so adding a new courier (e.g. a real
 * Pathao API) never touches the admin/vendor flow — you just add a driver and
 * register its slug in CourierDriverFactory.
 */
interface CourierDriverInterface
{
    /** Does this driver talk to a real courier API (vs. manual booking)? */
    public function supportsApi(): bool;

    /**
     * Create a parcel/consignment for the given courier.
     *
     * @param  array  $payload  invoice, recipient_name, recipient_phone,
     *                          recipient_address, cod_amount, note,
     *                          (optional) tracking_id for manual couriers.
     * @return array{success:bool, message?:string, error?:string, manual?:bool,
     *               tracking_id?:?string, consignment_id?:?string, status_code?:?int, data?:mixed}
     */
    public function createParcel(Courier $courier, array $payload): array;

    /**
     * Connectivity / credential check.
     *
     * @return array{success:bool, message:string, level:string}
     */
    public function testConnection(Courier $courier): array;
}
