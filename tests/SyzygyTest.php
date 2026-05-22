<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\Syzygy;

final class SyzygyTest extends TestCase
{
    public function testNextNewMoonFindsPhaseCrossing(): void
    {
        $tjd = Syzygy::nextNewMoon(2451545.0, Catalog::SEFLG_SPEED);

        self::assertGreaterThan(2451545.0, $tjd);
        self::assertLessThan(2451545.0 + 35.0, $tjd);
        self::assertPhaseAt($tjd, Syzygy::PHASE_NEW_MOON, 1e-7);
    }

    public function testNextFullMoonFindsPhaseCrossing(): void
    {
        $tjd = Syzygy::nextFullMoon(2451545.0, Catalog::SEFLG_SPEED);

        self::assertGreaterThan(2451545.0, $tjd);
        self::assertLessThan(2451545.0 + 35.0, $tjd);
        self::assertPhaseAt($tjd, Syzygy::PHASE_FULL_MOON, 1e-7);
    }

    public function testQuarterPhaseOrderAfterJ2000(): void
    {
        $newMoon = Syzygy::nextNewMoon(2451545.0, Catalog::SEFLG_SPEED);
        $firstQuarter = Syzygy::nextFirstQuarter(2451545.0, Catalog::SEFLG_SPEED);
        $fullMoon = Syzygy::nextFullMoon(2451545.0, Catalog::SEFLG_SPEED);
        $lastQuarter = Syzygy::nextLastQuarter(2451545.0, Catalog::SEFLG_SPEED);

        self::assertGreaterThan(2451545.0, $newMoon);
        self::assertGreaterThan($newMoon, $firstQuarter);
        self::assertGreaterThan($firstQuarter, $fullMoon);
        self::assertGreaterThan($fullMoon, $lastQuarter);

        self::assertPhaseAt($newMoon, Syzygy::PHASE_NEW_MOON, 1e-7);
        self::assertPhaseAt($firstQuarter, Syzygy::PHASE_FIRST_QUARTER, 1e-7);
        self::assertPhaseAt($fullMoon, Syzygy::PHASE_FULL_MOON, 1e-7);
        self::assertPhaseAt($lastQuarter, Syzygy::PHASE_LAST_QUARTER, 1e-7);
    }

    public function testNextPhaseAcceptsEquivalentAngles(): void
    {
        $expected = Syzygy::nextNewMoon(2451545.0, Catalog::SEFLG_SPEED);
        $actual = Syzygy::nextPhase(360.0, 2451545.0, Catalog::SEFLG_SPEED);

        self::assertEqualsWithDelta($expected, $actual, 1e-12);
    }

    public function testNextPhaseUtConvertsUtToEtAndBack(): void
    {
        $ut = 2451545.0;
        $expectedEt = Syzygy::nextFullMoon(
            $ut + DeltaT::deltatEx($ut, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED
        );

        $actualUt = Syzygy::nextFullMoonUt($ut, Catalog::SEFLG_SPEED);

        self::assertEqualsWithDelta(
            $expectedEt - DeltaT::deltatEx($expectedEt, Catalog::SEFLG_SPEED),
            $actualUt,
            1e-12
        );
    }

    public function testNextPhaseReturnsEarlierDateOnUnsupportedRange(): void
    {
        $tjd = Syzygy::nextNewMoon(2818001.0, Catalog::SEFLG_SPEED);

        self::assertLessThan(2818001.0, $tjd);
    }

    private static function assertPhaseAt(float $tjdEt, float $expectedPhase, float $delta): void
    {
        $moon = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MOON, Catalog::SEFLG_SPEED);
        $sun = Calculator::calcApparentFlags($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertNotSame(-1, $moon['rc']);
        self::assertNotSame(-1, $sun['rc']);

        $phase = Angle::degnorm($moon['xx'][0] - $sun['xx'][0]);

        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($phase, $expectedPhase), $delta);
    }
}