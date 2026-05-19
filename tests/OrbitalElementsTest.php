<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\OrbitalElements;
use SwissEph\SwissDate;

final class OrbitalElementsTest extends TestCase
{
    public function testMercuryOrbitalElementsFromCurrentMoshierState(): void
    {
        $result = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(0.387098400612, $result['dret'][0], 1e-12);
        self::assertEqualsWithDelta(0.205630087362, $result['dret'][1], 1e-12);
        self::assertEqualsWithDelta(7.005094775744, $result['dret'][2], 1e-12);
        self::assertEqualsWithDelta(48.330747832153, $result['dret'][3], 1e-12);
        self::assertEqualsWithDelta(29.125393460178, $result['dret'][4], 1e-12);
        self::assertEqualsWithDelta(77.456141292331, $result['dret'][5], 1e-12);
        self::assertEqualsWithDelta(174.796942441083, $result['dret'][6], 1e-12);
        self::assertEqualsWithDelta(176.495798960690, $result['dret'][7], 1e-12);
        self::assertEqualsWithDelta(175.683670388716, $result['dret'][8], 1e-12);
        self::assertEqualsWithDelta(0.240851584440, $result['dret'][10], 1e-12);
        self::assertEqualsWithDelta(4.092343165108, $result['dret'][11], 1e-12);
        self::assertEqualsWithDelta(-115.878740543620, $result['dret'][13], 1e-9);
        self::assertEqualsWithDelta(2451502.287570438348, $result['dret'][14], 1e-9);
        self::assertEqualsWithDelta(0.307499322676, $result['dret'][15], 1e-12);
        self::assertEqualsWithDelta(0.466697478547, $result['dret'][16], 1e-12);
    }

    public function testEarthOrbitalElementsAreSupported(): void
    {
        $result = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED
        );

        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(1.000448581675, $result['dret'][0], 1e-12);
        self::assertEqualsWithDelta(0.017118424418, $result['dret'][1], 1e-12);
        self::assertEqualsWithDelta(101.810286632224, $result['dret'][5], 1e-12);
        self::assertEqualsWithDelta(100.426967120588, $result['dret'][9], 1e-12);
        self::assertSame(0.0, $result['dret'][13]);
    }

    public function testPlutoAsteroidNumberMapsToMainPluto(): void
    {
        $expected = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_PLUTO,
            Catalog::SEFLG_SPEED
        );

        $actual = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_AST_OFFSET + 134340,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testUnsupportedBodyReturnsError(): void
    {
        $result = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('not implemented', $result['error']);
        self::assertSame(array_fill(0, 17, 0.0), $result['dret']);
    }

    public function testOrbitalElementsUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $ut = OrbitalElements::getUt($tjdUt, Catalog::SE_MERCURY, $flags);
        $et = OrbitalElements::get($tjdEt, Catalog::SE_MERCURY, $flags);

        self::assertSame($et, $ut);
    }

    public function testCalculatorDelegatesToOrbitalElements(): void
    {
        $expected = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::getOrbitalElements(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testOrbitMaxMinTrueDistanceUsesOrbitalElementsAndCurrentRadius(): void
    {
        $result = OrbitalElements::maxMinTrueDistance(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(0.466697478547, $result['max'], 1e-12);
        self::assertEqualsWithDelta(0.307499322676, $result['min'], 1e-12);
        self::assertEqualsWithDelta(0.466471713567, $result['true'], 1e-12);
    }

    public function testOrbitMaxMinTrueDistanceUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $ut = OrbitalElements::maxMinTrueDistanceUt($tjdUt, Catalog::SE_MERCURY, $flags);
        $et = OrbitalElements::maxMinTrueDistance($tjdEt, Catalog::SE_MERCURY, $flags);

        self::assertSame($et, $ut);
    }

    public function testOrbitMaxMinTrueDistanceReturnsErrorForUnsupportedBody(): void
    {
        $result = OrbitalElements::maxMinTrueDistance(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame(0.0, $result['max']);
        self::assertSame(0.0, $result['min']);
        self::assertSame(0.0, $result['true']);
        self::assertStringContainsString('not implemented', $result['error']);
    }

    public function testCalculatorDelegatesToOrbitMaxMinTrueDistance(): void
    {
        $expected = OrbitalElements::maxMinTrueDistance(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::orbitMaxMinTrueDistance(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testMoonOrbitalElementsAreSupportedGeocentrically(): void
    {
        $result = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(0.002548924409, $result['dret'][0], 1e-12);
        self::assertEqualsWithDelta(0.064409092929, $result['dret'][1], 1e-12);
        self::assertEqualsWithDelta(5.272126974141, $result['dret'][2], 1e-12);
        self::assertEqualsWithDelta(123.822907760507, $result['dret'][3], 1e-12);
        self::assertEqualsWithDelta(307.877348964684, $result['dret'][4], 1e-12);
        self::assertEqualsWithDelta(71.700256725191, $result['dret'][5], 1e-12);
        self::assertEqualsWithDelta(147.879667417414, $result['dret'][6], 1e-12);
        self::assertEqualsWithDelta(151.550422511773, $result['dret'][7], 1e-12);
        self::assertEqualsWithDelta(149.739370747107, $result['dret'][8], 1e-12);
        self::assertEqualsWithDelta(219.579924142605, $result['dret'][9], 1e-12);

        self::assertEqualsWithDelta(0.073804902721, $result['dret'][10], 1e-12);
        self::assertEqualsWithDelta(13.354767759998, $result['dret'][11], 1e-12);
        self::assertEqualsWithDelta(-29.104737252275, $result['dret'][13], 1e-12);
        self::assertEqualsWithDelta(0.002384750500, $result['dret'][15], 1e-12);
        self::assertEqualsWithDelta(0.002713098318, $result['dret'][16], 1e-12);
    }

    public function testOrbitalElementsAcceptAstronomicalAlmanacMassFlag(): void
    {
        $result = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ORBEL_AA
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_ORBEL_AA | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(0.387098443006, $result['dret'][0], 1e-12);
        self::assertEqualsWithDelta(0.205629955796, $result['dret'][1], 1e-12);
        self::assertEqualsWithDelta(7.005094775744, $result['dret'][2], 1e-12);
        self::assertEqualsWithDelta(48.330747832153, $result['dret'][3], 1e-12);
        self::assertEqualsWithDelta(77.456144119656, $result['dret'][5], 1e-12);
        self::assertEqualsWithDelta(0.240851643998, $result['dret'][10], 1e-12);
        self::assertEqualsWithDelta(4.092342153144, $result['dret'][11], 1e-12);

        $regular = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertNotEqualsWithDelta($regular['dret'][0], $result['dret'][0], 1e-12);
    }

    public function testAstronomicalAlmanacMassFlagChangesOuterPlanetElements(): void
    {
        $regular = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_SATURN,
            Catalog::SEFLG_SPEED
        );

        $aa = OrbitalElements::get(
            2451545.000738760,
            Catalog::SE_SATURN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ORBEL_AA
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_ORBEL_AA | Catalog::SEFLG_SWIEPH, $aa['rc']);
        self::assertNotEqualsWithDelta($regular['dret'][0], $aa['dret'][0], 1e-10);
        self::assertNotEqualsWithDelta($regular['dret'][10], $aa['dret'][10], 1e-10);
    }

    public function testOrbitalElementsReturnsErrorOutsideMoshierPlanetRange(): void
    {
        $result = OrbitalElements::get(
            2818001.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('outside Moshier planet range', $result['error']);
        self::assertSame(array_fill(0, 17, 0.0), $result['dret']);
    }

    public function testOrbitalElementsReturnsErrorOutsideMoshierMoonRange(): void
    {
        $result = OrbitalElements::get(
            8000017.0,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('outside Moshier Moon range', $result['error']);
        self::assertSame(array_fill(0, 17, 0.0), $result['dret']);
    }

    public function testOrbitDistanceReturnsErrorOutsideMoshierRange(): void
    {
        $result = OrbitalElements::maxMinTrueDistance(
            2818001.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('outside Moshier planet range', $result['error']);
        self::assertSame(0.0, $result['max']);
        self::assertSame(0.0, $result['min']);
        self::assertSame(0.0, $result['true']);
    }
}