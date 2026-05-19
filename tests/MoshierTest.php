<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Moshier;

final class MoshierTest extends TestCase
{
    public function testTimeParameterMatchesMoshierScale(): void
    {
        self::assertSame(0.0, Moshier::timeParameter(Moshier::J2000));
        self::assertSame(1.0, Moshier::timeParameter(Moshier::J2000 + Moshier::TIMESCALE));
        self::assertSame(-1.0, Moshier::timeParameter(Moshier::J2000 - Moshier::TIMESCALE));
    }

    public function testMods3600MatchesSwissEphemerisMacro(): void
    {
        self::assertSame(0.0, Moshier::mods3600(0.0));
        self::assertSame(1.0, Moshier::mods3600(1296001.0));
        self::assertSame(1295999.0, Moshier::mods3600(-1.0));
    }

    public function testMeanArgumentsAtJ2000MatchSwissEphemerisConstants(): void
    {
        self::assertEqualsWithDelta(252.25090552, Moshier::meanArgumentDegrees(0, Moshier::J2000), 1e-12);
        self::assertEqualsWithDelta(100.46645683, Moshier::meanArgumentDegrees(2, Moshier::J2000), 1e-12);
        self::assertEqualsWithDelta(239.0255985, Moshier::meanArgumentDegrees(8, Moshier::J2000), 1e-12);
    }

    public function testMeanArgumentsReturnsAllNineArguments(): void
    {
        $arguments = Moshier::meanArguments(Moshier::J2000);

        self::assertCount(9, $arguments);
        self::assertEqualsWithDelta(deg2rad(100.46645683), $arguments[2], 1e-14);
    }

    public function testHarmonicSinCos(): void
    {
        $harmonics = Moshier::harmonicSinCos(deg2rad(30.0), 3);

        self::assertEqualsWithDelta(0.5, $harmonics[1]['sin'], 1e-15);
        self::assertEqualsWithDelta(sqrt(3.0) / 2.0, $harmonics[1]['cos'], 1e-15);

        self::assertEqualsWithDelta(sqrt(3.0) / 2.0, $harmonics[2]['sin'], 1e-15);
        self::assertEqualsWithDelta(0.5, $harmonics[2]['cos'], 1e-15);

        self::assertEqualsWithDelta(1.0, $harmonics[3]['sin'], 1e-15);
        self::assertEqualsWithDelta(0.0, $harmonics[3]['cos'], 1e-15);
    }

    public function testInvalidArgumentIndexThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Moshier::meanArgument(9, Moshier::J2000);
    }

    public function testNegativeHarmonicCountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Moshier::harmonicSinCos(1.0, -1);
    }

    public function testPlanetRangeMatchesSwissEphemerisMoshierLimits(): void
    {
        self::assertTrue(Moshier::isInPlanetRange(625000.2));
        self::assertTrue(Moshier::isInPlanetRange(2818000.8));

        self::assertFalse(Moshier::isInPlanetRange(625000.199999));
        self::assertFalse(Moshier::isInPlanetRange(2818000.800001));

        self::assertFalse(Moshier::isInPlanetRange(625000.2, false));
        self::assertFalse(Moshier::isInPlanetRange(2818000.8, false));
    }

    public function testPlanetRangeErrorMessage(): void
    {
        self::assertSame(
            'jd 624999.000000 outside Moshier planet range 625000.50 .. 2818000.50',
            Moshier::planetRangeError(624999.0)
        );
    }
}