<?php

namespace App\Contracts;

use App\Models\Courier;

/**
 * Optional capability for API drivers that can run low-level diagnostics
 * (DNS / SSL / full). The admin diagnose endpoint checks `instanceof` this
 * interface, so manual drivers simply don't implement it.
 *
 * Each method returns: array{success:bool, message:string, level:string, detail?:?string}
 */
interface CourierDiagnosticsInterface
{
    public function testDns(Courier $courier): array;

    public function testSsl(Courier $courier): array;

    public function fullTest(Courier $courier): array;
}
