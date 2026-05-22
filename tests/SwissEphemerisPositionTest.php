<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\CalculationResult;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\EphemerisFiles;
use SwissEph\SwissEphemerisPosition;
use SwissEph\Tests\Support\EphemerisFixtureFactory;

final class SwissEphemerisPositionTest extends TestCase
{
    protected function setUp(): void
    {
        EphemerisFiles::setPath(EphemerisFixtureFactory::path());
    }

    public function testCartesianReturnsVectorFromEphemerisFiles(): void
    {
        $vector = SwissEphemerisPosition::cartesian(Catalog::SE_MERCURY, 2451545.0);

        self::assertEqualsWithDelta(0.05, $vector[0], 1e-15);
        self::assertEqualsWithDelta(0.102, $vector[1], 1e-15);
        self::assertEqualsWithDelta(0.015, $vector[2], 1e-15);
        self::assertEqualsWithDelta(0.002, $vector[3], 1e-15);
        self::assertEqualsWithDelta(-0.004, $vector[4], 1e-15);
        self::assertEqualsWithDelta(0.0, $vector[5], 1e-15);
    }

    public function testPolarReturnsEclipticCoordinatesFromEphemerisFiles(): void
    {
        $xx = SwissEphemerisPosition::polar(Catalog::SE_MERCURY, 2451545.0);

        self::assertEqualsWithDelta(63.88608736970928, $xx[0], 1e-12);
        self::assertEqualsWithDelta(7.52222639092815, $xx[1], 1e-12);
        self::assertEqualsWithDelta(0.11458184847522751, $xx[2], 1e-15);
        self::assertEqualsWithDelta(-1.7938232271609784, $xx[3], 1e-12);
        self::assertEqualsWithDelta(0.17748873023430972, $xx[4], 1e-12);
        self::assertEqualsWithDelta(-0.002688034833602718, $xx[5], 1e-15);
    }

    public function testCartesianCanSkipSpeed(): void
    {
        $vector = SwissEphemerisPosition::cartesian(Catalog::SE_MERCURY, 2451545.0, false);

        self::assertEqualsWithDelta(0.05, $vector[0], 1e-15);
        self::assertSame(0.0, $vector[3]);
        self::assertSame(0.0, $vector[4]);
        self::assertSame(0.0, $vector[5]);
    }

    public function testPolarCanSkipSpeed(): void
    {
        $xx = SwissEphemerisPosition::polar(Catalog::SE_MERCURY, 2451545.0, false);

        self::assertEqualsWithDelta(63.88608736970928, $xx[0], 1e-12);
        self::assertEqualsWithDelta(7.52222639092815, $xx[1], 1e-12);
        self::assertEqualsWithDelta(0.11458184847522751, $xx[2], 1e-15);
        self::assertSame(0.0, $xx[3]);
        self::assertSame(0.0, $xx[4]);
        self::assertSame(0.0, $xx[5]);
    }

    public function testPolarResultKeepsErrorContext(): void
    {
        $result = SwissEphemerisPosition::polarResult(Catalog::SE_VENUS, 2451545.0);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('ephemeris body descriptor not found', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCartesianResultKeepsFileContext(): void
    {
        $result = SwissEphemerisPosition::cartesianResult(Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_MOON, $result['body']);
        self::assertSame(Catalog::SE_MOON, $result['ipl']);
        self::assertSame(EphemerisFiles::TYPE_MOON, $result['type']);
        self::assertSame('semo_18.se1', $result['file']);
        self::assertSame(0, $result['segment']);

        self::assertEqualsWithDelta(0.002, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(0.0011613375635611345, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(0.00486325971581427, $result['vector'][2], 1e-18);
    }

    public function testIsAvailableReportsExistingEphemerisBody(): void
    {
        self::assertTrue(SwissEphemerisPosition::isAvailable(Catalog::SE_MERCURY, 2451545.0));
    }

    public function testCartesianThrowsWhenBodyIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ephemeris body descriptor not found');

        SwissEphemerisPosition::cartesian(Catalog::SE_VENUS, 2451545.0);
    }

    public function testPolarCalculationResultReturnsCalculationResult(): void
    {
        $result = SwissEphemerisPosition::polarCalculationResult(Catalog::SE_MERCURY, 2451545.0);

        self::assertInstanceOf(CalculationResult::class, $result);
        self::assertSame(Catalog::SE_OK, $result->rc);
        self::assertSame('', $result->error);
        self::assertEqualsWithDelta(63.88608736970928, $result->longitude(), 1e-12);
        self::assertEqualsWithDelta(7.52222639092815, $result->latitude(), 1e-12);
        self::assertEqualsWithDelta(0.11458184847522751, $result->distance(), 1e-15);
    }

    public function testPolarCalculationResultKeepsErrors(): void
    {
        $result = SwissEphemerisPosition::polarCalculationResult(Catalog::SE_VENUS, 2451545.0);

        self::assertInstanceOf(CalculationResult::class, $result);
        self::assertSame(Catalog::SE_ERR, $result->rc);
        self::assertSame('ephemeris body descriptor not found', $result->error);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result->xx);
    }

    public function testCartesianUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0 - DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SWIEPH);

        $utVector = SwissEphemerisPosition::cartesianUt(Catalog::SE_MERCURY, $tjdUt);
        $etVector = SwissEphemerisPosition::cartesian(Catalog::SE_MERCURY, 2451545.0);

        self::assertEqualsWithDelta($etVector[0], $utVector[0], 1e-12);
        self::assertEqualsWithDelta($etVector[1], $utVector[1], 1e-12);
        self::assertEqualsWithDelta($etVector[2], $utVector[2], 1e-12);
    }

    public function testPolarUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0 - DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SWIEPH);

        $ut = SwissEphemerisPosition::polarUt(Catalog::SE_MERCURY, $tjdUt);
        $et = SwissEphemerisPosition::polar(Catalog::SE_MERCURY, 2451545.0);

        self::assertEqualsWithDelta($et[0], $ut[0], 1e-12);
        self::assertEqualsWithDelta($et[1], $ut[1], 1e-12);
        self::assertEqualsWithDelta($et[2], $ut[2], 1e-12);
    }

    public function testPolarUtCalculationResultReturnsCalculationResult(): void
    {
        $tjdUt = 2451545.0 - DeltaT::deltatEx(2451545.0, Catalog::SEFLG_SWIEPH);

        $result = SwissEphemerisPosition::polarUtCalculationResult(Catalog::SE_MERCURY, $tjdUt);

        self::assertInstanceOf(CalculationResult::class, $result);
        self::assertSame(Catalog::SE_OK, $result->rc);
        self::assertEqualsWithDelta(63.88608736970928, $result->longitude(), 1e-12);
    }
}