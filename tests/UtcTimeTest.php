<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\SwissDate;
use SwissEph\UtcTime;

final class UtcTimeTest extends TestCase
{
    public function testUtcToJdForRegularUtcDate(): void
    {
        $result = UtcTime::utcToJd(2000, 1, 1, 0, 0, 0.0, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertEqualsWithDelta(2451544.5007428704, $result['tt'], 1e-12);
        self::assertEqualsWithDelta(2451544.5000041146, $result['ut1'], 1e-12);
    }

    public function testUtcToJdAcceptsValidLeapSecond(): void
    {
        $result = UtcTime::utcToJd(2016, 12, 31, 23, 59, 60.0, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertEqualsWithDelta(2457754.5007891669, $result['tt'], 1e-12);
        self::assertEqualsWithDelta(2457754.4999952596, $result['ut1'], 1e-12);
    }

    public function testUtcToJdRejectsInvalidLeapSecond(): void
    {
        $result = UtcTime::utcToJd(2016, 12, 30, 23, 59, 60.0, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame('invalid time (no leap second!): 23:59:60.00', $result['error']);
    }

    public function testUtcBefore1972IsTreatedAsUt1(): void
    {
        $result = UtcTime::utcToJd(1971, 12, 31, 23, 59, 59.0, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertEqualsWithDelta(2441317.500477199, $result['tt'], 1e-12);
        self::assertEqualsWithDelta(2441317.499988426, $result['ut1'], 1e-12);
    }

    public function testJdetToUtcRoundTripsRegularDate(): void
    {
        $result = UtcTime::jdetToUtc(2451544.5007428704, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(2000, $result['year']);
        self::assertSame(1, $result['month']);
        self::assertSame(1, $result['day']);
        self::assertSame(0, $result['hour']);
        self::assertSame(0, $result['minute']);
        self::assertEqualsWithDelta(0.0, $result['second'], 1e-5);
    }

    public function testJdetToUtcRoundTripsLeapSecond(): void
    {
        $result = UtcTime::jdetToUtc(2457754.5007891669, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(2016, $result['year']);
        self::assertSame(12, $result['month']);
        self::assertSame(31, $result['day']);
        self::assertSame(23, $result['hour']);
        self::assertSame(59, $result['minute']);
        self::assertEqualsWithDelta(60.0, $result['second'], 1e-5);
    }

    public function testJdut1ToUtcRoundTripsLeapSecond(): void
    {
        $result = UtcTime::jdut1ToUtc(2457754.4999952596, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(2016, $result['year']);
        self::assertSame(12, $result['month']);
        self::assertSame(31, $result['day']);
        self::assertSame(23, $result['hour']);
        self::assertSame(59, $result['minute']);
        self::assertEqualsWithDelta(60.0, $result['second'], 1e-5);
    }
}