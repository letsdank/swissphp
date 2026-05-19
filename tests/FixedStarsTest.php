<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\FixedStars;
use SwissEph\SwissDate;

final class FixedStarsTest extends TestCase
{
    public function testSiriusCanBeReturnedInEquatorialJ2000Coordinates(): void
    {
        $result = FixedStars::fixstar(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000 | Catalog::SEFLG_EQUATORIAL
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000 | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('Sirius', $result['star']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(101.287155330000, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-16.716115860000, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(543932.929635558161, $result['xx'][2], 1e-6);
        self::assertEqualsWithDelta(-0.000000433602, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(-0.000000930157, $result['xx'][4], 1e-12);
    }

    public function testSiriusCanBeReturnedInEclipticJ2000Coordinates(): void
    {
        $result = FixedStars::fixstar(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        self::assertEqualsWithDelta(104.081662153609, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-39.605237221305, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(543932.929635558161, $result['xx'][2], 1e-6);
        self::assertEqualsWithDelta(-0.000000485898, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(-0.000000621867, $result['xx'][4], 1e-12);
    }

    public function testSiriusPositionUsesProperMotionAndPrecession(): void
    {
        $result = FixedStars::fixstar(
            'Sirius',
            2458849.5,
            Catalog::SEFLG_SPEED
        );

        self::assertEqualsWithDelta(104.352724572034, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-39.609837387510, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.000023444059, $result['xx'][3], 1e-12);
    }

    public function testFixedStarAliasesAreSupported(): void
    {
        $canonical = FixedStars::fixstar('Aldebaran', 2451545.0, Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000);
        $alias = FixedStars::fixstar('alpha-tauri', 2451545.0, Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000);

        self::assertSame($canonical, $alias);
        self::assertEqualsWithDelta(69.789188544711, $alias['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-5.467316171971, $alias['xx'][1], 1e-12);
    }

    public function testFixedStarCanReturnCartesianCoordinates(): void
    {
        $result = FixedStars::fixstar(
            'Sirius',
            2451545.0,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ | Catalog::SEFLG_J2000
        );

        self::assertEqualsWithDelta(-101963.071759461396, $result['xx'][0], 1e-6);
        self::assertEqualsWithDelta(406482.575983570772, $result['xx'][1], 1e-6);
        self::assertEqualsWithDelta(-346754.205974573037, $result['xx'][2], 1e-6);
        self::assertEqualsWithDelta(0.004362867069, $result['xx'][3], 1e-12);
    }

    public function testFixedStarWithoutSpeedZerosSpeedComponents(): void
    {
        $result = FixedStars::fixstar('Sirius', 2451545.0, Catalog::SEFLG_J2000);

        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testFixstarUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $ut = FixedStars::fixstarUt('Sirius', $tjdUt, $flags);
        $et = FixedStars::fixstar('Sirius', $tjdEt, $flags);

        self::assertSame($et, $ut);
    }

    public function testFixedStarMagnitudeCanBeReturned(): void
    {
        $result = FixedStars::fixstarMagnitude('dog star');

        self::assertSame(SwissDate::OK, $result['rc']);
        self::assertSame('Sirius', $result['star']);
        self::assertEqualsWithDelta(-1.46, $result['mag'], 1e-12);
        self::assertSame('', $result['error']);
    }

    public function testUnknownFixedStarReturnsError(): void
    {
        $result = FixedStars::fixstar('Unknown Star', 2451545.0, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
        self::assertStringContainsString('not found', $result['error']);
    }

    public function testCalculatorDelegatesToFixedStars(): void
    {
        $expected = FixedStars::fixstar('Sirius', 2451545.0, Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000);
        $actual = Calculator::fixstar('Sirius', 2451545.0, Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000);

        self::assertSame($expected, $actual);
    }

    public function testFixedStarNamesReturnBuiltInCatalogNames(): void
    {
        self::assertSame(
            ['Sirius', 'Aldebaran', 'Regulus', 'Spica'],
            FixedStars::names()
        );
    }

    public function testFixedStarExistsChecksCanonicalNamesAndAliases(): void
    {
        self::assertTrue(FixedStars::exists('Sirius'));
        self::assertTrue(FixedStars::exists('dog-star'));
        self::assertTrue(FixedStars::exists('alpha tauri'));
        self::assertFalse(FixedStars::exists('Unknown Star'));
    }

    public function testCalculatorDelegatesFixedStarCatalogLookups(): void
    {
        self::assertSame(FixedStars::names(), Calculator::fixedStarNames());
        self::assertTrue(Calculator::fixedStarExists('Sirius'));
        self::assertFalse(Calculator::fixedStarExists('Unknown Star'));
    }
}