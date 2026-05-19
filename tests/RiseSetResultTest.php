<?php

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\RiseSetResult;

final class RiseSetResultTest extends TestCase
{
    public function testCreatesResultFromArray(): void
    {
        $result = RiseSetResult::fromArray([
            'tjdUt' => 2451545.5,
            'azimuth' => 90.0,
            'trueAltitude' => -0.833,
            'apparentAltitude' => -0.25,
        ]);

        self::assertSame(2451545.5, $result->tjdUt);
        self::assertSame(90.0, $result->azimuth);
        self::assertSame(-0.833, $result->trueAltitude);
        self::assertSame(-0.25, $result->apparentAltitude);
    }

    public function testConvertsResultToArray(): void
    {
        $result = new RiseSetResult(
            2451545.5,
            90.0,
            -0.833,
            -0.25
        );

        self::assertSame([
            'tjdUt' => 2451545.5,
            'azimuth' => 90.0,
            'trueAltitude' => -0.833,
            'apparentAltitude' => -0.25,
        ], $result->toArray());
    }

    public function testNamedAccessors(): void
    {
        $result = new RiseSetResult(
            2451545.0,
            90.0,
            -0.833,
            -0.25
        );

        self::assertEqualsWithDelta(0.583, $result->apparentRefraction(), 1e-12);
        self::assertSame('90°00\'00"', $result->azimuthDms());
        self::assertSame('-0°49\'59"', $result->trueAltitudeDms());
        self::assertSame('-0°15\'00"', $result->apparentAltitudeDms());
    }
}