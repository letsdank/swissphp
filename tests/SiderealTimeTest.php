<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SwissEph\SiderealTime;

final class SiderealTimeTest extends TestCase
{
    public function testSidtime0MatchesSwissEphemerisProbe(): void
    {
        $result = SiderealTime::sidtime0(2451545.0, 23.439291111, -0.003867);

        self::assertEqualsWithDelta(18.697138340829511, $result, 1e-7);
    }

    #[DataProvider('sidtimeProvider')]
    public function testSidtimeIsCloseToSwissEphemeris(float $jd, float $expected): void
    {
        self::assertEqualsWithDelta($expected, SiderealTime::sidtime($jd), 2e-5);
    }

    public function testMeanObliquityAtJ2000(): void
    {
        self::assertEqualsWithDelta(23.439279444444445, SiderealTime::meanObliquity(2451545.0), 1e-14);
    }

    /**
     * Values generated from Swiss Ephemeris 2.10.03 swe_sidtime().
     *
     * @return iterable<string, array{float, float}>
     */
    public static function sidtimeProvider(): iterable
    {
        yield 'J2000 noon' => [2451545.0, 18.697138162535065];
        yield 'J2000 midnight' => [2451544.5, 6.664283258298989];
        yield '2013-02-11' => [2456334.5, 9.414838074095076];
        yield '1698-10-09 noon' => [2341524.0, 13.237062203697885];
    }
}