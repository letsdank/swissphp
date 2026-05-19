<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\SwissDate;

final class SwissDateTest extends TestCase
{
    public function testJuldayMatchesSwissEphemerisFixtures(): void
    {
        self::assertSame(
            1729992.4375,
            SwissDate::julday(19, 2, 1964, 22.5, SwissDate::JULIAN_CALENDAR)
        );

        self::assertSame(
            1729994.4375,
            SwissDate::julday(19, 2, 1964, 22.5, SwissDate::GREGORIAN_CALENDAR)
        );

        self::assertSame(
            1729993.4375,
            SwissDate::julday(19, 2, 1965, 22.5, SwissDate::JULIAN_CALENDAR)
        );

        self::assertSame(
            1729995.4375,
            SwissDate::julday(19, 2, 1965, 22.5, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testRevjulMatchesSwissEphemerisFixtures(): void
    {
        self::assertSame(
            [
                'year' => 1698,
                'month' => 9,
                'day' => 29,
                'hour' => 12.0,
            ],
            SwissDate::revjul(2341524.0, SwissDate::JULIAN_CALENDAR)
        );
        self::assertSame(
            [
                'year' => 1698,
                'month' => 10,
                'day' => 9,
                'hour' => 12.0,
            ],
            SwissDate::revjul(2341524.0, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testDateConversionAcceptsValidDate(): void
    {
        $result = SwissDate::dateConversion(2000, 1, 1, 12.0, 'g');

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertSame(2451545.0, $result['jd']);
    }

    public function testDateConversionRejectsInvalidDateButStillReturnsJulianDay(): void
    {
        $result = SwissDate::dateConversion(1993, 1, 32, 0.0, 'g');

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame(
            SwissDate::julday(1993, 1, 32, 0.0, SwissDate::GREGORIAN_CALENDAR),
            $result['jd']
        );
    }

    public function testUtcTimeZoneConvertsLocalTimeToUtc(): void
    {
        self::assertEqualsWithDelta(
            [
                'year' => 2024,
                'month' => 1,
                'day' => 1,
                'hour' => 9,
                'minute' => 30,
                'second' => 0.0,
            ],
            SwissDate::utcTimeZone(2024, 1, 1, 12, 30, 0.0, 3.0),
            1e-9
        );
    }

    public function testUtcTimeZoneCrossesDateBoundary(): void
    {
        self::assertEqualsWithDelta(
            [
                'year' => 2023,
                'month' => 12,
                'day' => 31,
                'hour' => 21,
                'minute' => 30,
                'second' => 0.0,
            ],
            SwissDate::utcTimeZone(2024, 1, 1, 0, 30, 0.0, 3.0),
            1e-9
        );
    }
}