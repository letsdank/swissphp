<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Observer;

final class ObserverTest extends TestCase
{
    public function testCreatesObserverFromCoordinates(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);

        self::assertSame(30.0, $observer->longitude);
        self::assertSame(59.0, $observer->latitude);
        self::assertSame(120.0, $observer->altitude);
    }

    public function testCreatesObserverFromArrayWithDefaultAltitude(): void
    {
        $observer = Observer::fromArray([30.0, 59.0]);

        self::assertSame(30.0, $observer->longitude);
        self::assertSame(59.0, $observer->latitude);
        self::assertSame(0.0, $observer->altitude);
    }

    public function testConvertsObserverToArray(): void
    {
        self::assertSame([30.0, 59.0, 120.0], (new Observer(30.0, 59.0, 120.0))->toArray());
    }

    public function testWithAltitudeReturnsNewObserver(): void
    {
        $observer = new Observer(30.0, 59.0);
        $changed = $observer->withAltitude(250.0);

        self::assertNotSame($observer, $changed);
        self::assertSame(30.0, $changed->longitude);
        self::assertSame(59.0, $changed->latitude);
        self::assertSame(250.0, $changed->altitude);
    }

    public function testNormalizedLongitudeUsesSignedRange(): void
    {
        self::assertSame(-170.0, (new Observer(190.0, 0.0))->normalizedLongitude());
        self::assertSame(170.0, (new Observer(-190.0, 0.0))->normalizedLongitude());
    }

    public function testNormalizedLatitudeClampsToPoleRange(): void
    {
        self::assertSame(90.0, (new Observer(0.0, 120.0))->normalizedLatitude());
        self::assertSame(-90.0, (new Observer(0.0, -120.0))->normalizedLatitude());
        self::assertSame(59.0, (new Observer(0.0, 59.0))->normalizedLatitude());
    }

    public function testGeocentricVectorMatchesSwissFormulaFixture(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);
        $vector = $observer->geocentricVector(2451545.000738760);

        self::assertEqualsWithDelta(1.4283988983331e-5, $vector[0], 1e-15);
        self::assertEqualsWithDelta(-1.67498132390893e-5, $vector[1], 1e-15);
        self::assertEqualsWithDelta(3.63911507218709e-5, $vector[2], 1e-15);
        self::assertEqualsWithDelta(0.000105530313736938, $vector[3], 1e-15);
        self::assertEqualsWithDelta(8.99946654514365e-5, $vector[4], 1e-15);
        self::assertEqualsWithDelta(0.0, $vector[5], 1e-15);
    }

    public function testGeocentricPositionReturnsOnlyPositionComponents(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);

        self::assertSame(
            array_slice($observer->geocentricVector(2451545.000738760), 0, 3),
            $observer->geocentricPosition(2451545.000738760)
        );
    }

    public function testGeocentricVectorChangesWithAltitude(): void
    {
        $seaLevel = new Observer(30.0, 59.0, 0.0);
        $mountain = new Observer(30.0, 59.0, 3000.0);

        self::assertNotEqualsWithDelta(
            $seaLevel->geocentricVector(2451545.000738760)[2],
            $mountain->geocentricVector(2451545.000738760)[2],
            1e-12
        );
    }

    public function testGeocentricVectorCanUseMeanSiderealFrame(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);

        $apparent = $observer->geocentricVector(2451545.000738760, true);
        $mean = $observer->geocentricVector(2451545.000738760, false);

        self::assertGreaterThan(1e-10, abs($apparent[0] - $mean[0]));
    }
}