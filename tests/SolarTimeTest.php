<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\SolarTime;
use SwissEph\SwissDate;

final class SolarTimeTest extends TestCase
{
    public function testTimeEquationIsCloseToSwissEphemerisFixture(): void
    {
        $result = SolarTime::timeEquation(2341524.0);

        self::assertSame(SwissDate::OK, $result['rc']);

        // Swiss Ephemeris fixture: 0.00892947148829740037.
        // This implementation uses Meeus/NOAA approximation until swe_calc_ut(SE_SUN) is ported.
        self::assertEqualsWithDelta(0.00892947148829740037, $result['e'], 3e-6);
    }

    public function testLmtToLatIsCloseToSwissEphemerisFixture(): void
    {
        $result = SolarTime::lmtToLat(2451545.17231999989598989487, 17.234);

        self::assertSame(SwissDate::OK, $result['rc']);

        // Swiss Ephemeris fixture: 2451545.16999755566939711571.
        self::assertEqualsWithDelta(2451545.16999755566939711571, $result['tjd_lat'], 2e-5);
    }

    public function testLatToLmtIsCloseToSwissEphemerisFixture(): void
    {
        $result = SolarTime::latToLmt(2451545.17231999989598989487, -17.234);

        self::assertSame(SwissDate::OK, $result['rc']);

        // Swiss Ephemeris fixture: 2451545.17467474099248647690.
        self::assertEqualsWithDelta(2451545.17467474099248647690, $result['tjd_lmt'], 2e-5);
    }

    public function testLatAndLmtRoundTrip(): void
    {
        $tjdLmt = 2451545.17231999989598989487;
        $geoLon = 17.234;

        $lat = SolarTime::lmtToLat($tjdLmt, $geoLon);
        $lmt = SolarTime::latToLmt($lat['tjd_lat'], $geoLon);

        self::assertEqualsWithDelta($tjdLmt, $lmt['tjd_lmt'], 1e-7);
    }
}