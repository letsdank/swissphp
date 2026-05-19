<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Refraction;

final class RefractionTest extends TestCase
{
    public function testRefracConvertsTrueAltitudeToApparentAltitude(): void
    {
        $altitude = Refraction::refrac(
            10.0,
            1013.25,
            15.0,
            Catalog::SE_TRUE_TO_APP
        );

        self::assertEqualsWithDelta(10.088848271817637, $altitude, 1e-12);
    }

    public function testRefracConvertsApparentAltitudeToTrueAltitude(): void
    {
        $altitude = Refraction::refrac(
            10.089558098706,
            1013.25,
            15.0,
            Catalog::SE_APP_TO_TRUE
        );

        self::assertEqualsWithDelta(10.001252837439766, $altitude, 1e-12);
    }

    public function testRefracLeavesLowTrueAltitudeUnchangedWhenNotVisible(): void
    {
        $altitude = Refraction::refrac(
            -6.0,
            1013.25,
            15.0,
            Catalog::SE_TRUE_TO_APP
        );

        self::assertSame(-6.0, $altitude);
    }

    public function testExtendedConvertsTrueAltitudeToApparentAltitude(): void
    {
        $result = Refraction::extended(
            0.0,
            100.0,
            1000.0,
            15.0,
            Refraction::DEFAULT_LAPSE_RATE,
            Catalog::SE_TRUE_TO_APP
        );

        self::assertEqualsWithDelta(0.466369392581875, $result['altitude'], 1e-12);
        self::assertEqualsWithDelta(0.0, $result['trueAltitude'], 1e-12);
        self::assertEqualsWithDelta(0.466369392581875, $result['apparentAltitude'], 1e-12);
        self::assertEqualsWithDelta(0.466369392581875, $result['refraction'], 1e-12);
        self::assertEqualsWithDelta(-0.278382528549522, $result['dip'], 1e-12);
    }

    public function testExtendedConvertsApparentAltitudeToTrueAltitude(): void
    {
        $result = Refraction::extended(
            0.478112249198,
            100.0,
            1000.0,
            15.0,
            Refraction::DEFAULT_LAPSE_RATE,
            Catalog::SE_APP_TO_TRUE
        );

        self::assertEqualsWithDelta(0.013628725922276, $result['altitude'], 1e-12);
        self::assertEqualsWithDelta(0.013628725922276, $result['trueAltitude'], 1e-12);
        self::assertEqualsWithDelta(0.478112249198, $result['apparentAltitude'], 1e-12);
        self::assertEqualsWithDelta(0.464483523275724, $result['refraction'], 1e-12);
        self::assertEqualsWithDelta(-0.278382528549522, $result['dip'], 1e-12);
    }

    public function testExtendedMirrorsAltitudeAboveNinetyDegrees(): void
    {
        $result = Refraction::extended(
            91.0,
            100.0,
            1000.0,
            15.0,
            Refraction::DEFAULT_LAPSE_RATE,
            Catalog::SE_TRUE_TO_APP
        );

        self::assertEqualsWithDelta(89.000274790594148, $result['altitude'], 1e-12);
        self::assertEqualsWithDelta(89.0, $result['trueAltitude'], 1e-12);
        self::assertEqualsWithDelta(0.000274790594151, $result['refraction'], 1e-12);
    }

    public function testHorizonDipMatchesExtendedRefractionDip(): void
    {
        $dip = Refraction::horizonDip(
            100.0,
            1000.0,
            15.0
        );

        self::assertEqualsWithDelta(-0.278382528549522, $dip, 1e-12);
    }
}