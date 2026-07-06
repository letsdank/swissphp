<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\SwissDate;
use SwissEph\Syzygy;
use SwissEph\SyzygyResult;

final class SyzygyResultTest extends TestCase
{
    public function testNextPhaseExReturnsPhaseDetails(): void
    {
        $result = Syzygy::nextPhaseEx(Syzygy::PHASE_NEW_MOON, 2451545.0, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertGreaterThan(2451545.0, $result['tjd']);
        self::assertSame(Syzygy::PHASE_NEW_MOON, $result['targetPhase']);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($result['phaseAngle'], Syzygy::PHASE_NEW_MOON), 1e-7);
        self::assertEqualsWithDelta($result['phaseAngle'], $result['attributes'][0], 1e-12);
        self::assertEqualsWithDelta($result['sunLongitude'], $result['attributes'][1], 1e-12);
    }

    public function testNextPhaseExUtConvertsUtToEtAndBack(): void
    {
        $ut = 2451545.0;
        $expectedEt = Syzygy::nextPhaseEx(
            Syzygy::PHASE_FULL_MOON,
            $ut + DeltaT::deltatEx($ut, Catalog::SEFLG_SPEED),
            Catalog::SEFLG_SPEED
        );

        $actualUt = Syzygy::nextPhaseExUt(Syzygy::PHASE_FULL_MOON, $ut, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::OK, $actualUt['rc']);
        self::assertEqualsWithDelta(
            $expectedEt['tjd'] - DeltaT::deltatEx($expectedEt['tjd'], Catalog::SEFLG_SPEED),
            $actualUt['tjd'],
            1e-12
        );
        self::assertEqualsWithDelta($expectedEt['phaseAngle'], $actualUt['phaseAngle'], 1e-7);
    }

    public function testNextPhaseResultWrapsArrayResult(): void
    {
        $result = Syzygy::nextPhaseResult(Syzygy::PHASE_FIRST_QUARTER, 2451545.0, Catalog::SEFLG_SPEED);

        self::assertInstanceOf(SyzygyResult::class, $result);
        self::assertSame(SwissDate::OK, $result->rc);
        self::assertSame(Syzygy::PHASE_FIRST_QUARTER, $result->targetPhase);
        self::assertEqualsWithDelta(0.0, Angle::difdeg2n($result->phaseAngle, Syzygy::PHASE_FIRST_QUARTER), 1e-7);
        self::assertSame($result->targetPhase, $result->toArray()['targetPhase']);
    }

    public function testNextPhaseExReturnsErrorWhenCrossingCannotBeFound(): void
    {
        $result = Syzygy::nextPhaseEx(Syzygy::PHASE_NEW_MOON, 2818001.0, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertLessThan(2818001.0, $result['tjd']);
        self::assertNotSame('', $result['error']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attributes']);
    }
}