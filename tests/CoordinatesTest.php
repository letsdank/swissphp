<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Coordinates;

final class CoordinatesTest extends TestCase
{
    public function testCotransEclipticToEquatorialMatchesSwissEphemeris(): void
    {
        $result = Coordinates::cotrans([120.0, 5.0, 1.2], -23.439291111);

        self::assertEqualsWithDelta(123.348955232022973, $result[0], 1e-12);
        self::assertEqualsWithDelta(25.032866909930362, $result[1], 1e-12);
        self::assertSame(1.2, $result[2]);
    }

    public function testCotransRoundTrip(): void
    {
        $equatorial = Coordinates::cotrans([120.0, 5.0, 1.2], -23.439291111);
        $ecliptic = Coordinates::cotrans($equatorial, 23.439291111);

        self::assertEqualsWithDelta(120.0, $ecliptic[0], 1e-12);
        self::assertEqualsWithDelta(5.0, $ecliptic[1], 1e-12);
        self::assertSame(1.2, $ecliptic[2]);
    }

    public function testCotransOnEquatorAndQuarterCircle(): void
    {
        self::assertEqualsWithDelta([0.0, 0.0, 1.0], Coordinates::cotrans([0.0, 0.0, 1.0], -23.439291111), 1e-12);

        $result = Coordinates::cotrans([90.0, 0.0, 1.0], -23.439291111);

        self::assertEqualsWithDelta(90.0, $result[0], 1e-12);
        self::assertEqualsWithDelta(23.439291111, $result[1], 1e-12);
        self::assertSame(1.0, $result[2]);
    }

    public function testCotransSpMatchesSwissEphemeris(): void
    {
        $result = Coordinates::cotransSp(
            [120.0, 5.0, 1.2, 0.11, -0.02, 0.003],
            -23.439291111
        );

        self::assertEqualsWithDelta(123.348955232022973, $result[0], 1e-12);
        self::assertEqualsWithDelta(25.032866909930362, $result[1], 1e-12);
        self::assertSame(1.2, $result[2]);
        self::assertEqualsWithDelta(0.113147090223690, $result[3], 1e-12);
        self::assertEqualsWithDelta(-0.043566213500711, $result[4], 1e-12);
        self::assertSame(0.003, $result[5]);
    }

    public function testPolcartAndCartpolRoundTrip(): void
    {
        $polar = [deg2rad(123.4), deg2rad(-12.3), 2.5];
        $cart = Coordinates::polcart($polar);
        $roundTrip = Coordinates::cartpol($cart);

        self::assertEqualsWithDelta($polar[0], $roundTrip[0], 1e-14);
        self::assertEqualsWithDelta($polar[1], $roundTrip[1], 1e-14);
        self::assertEqualsWithDelta($polar[2], $roundTrip[2], 1e-14);
    }
}