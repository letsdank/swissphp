<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\MeanApogee;
use SwissEph\MeanNode;
use SwissEph\NodesApsides;
use SwissEph\OsculatingApogee;
use SwissEph\SwissDate;
use SwissEph\TrueNode;

final class NodesApsidesTest extends TestCase
{
    public function testMoonMeanNodesAndApsidesUseExistingLunarPoints(): void
    {
        $tjdEt = 2451545.000738760;

        $result = NodesApsides::nodAps(
            $tjdEt,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        $meanNode = MeanNode::apparent($tjdEt);
        $meanApogee = MeanApogee::apparent($tjdEt);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertSame($meanNode, $result['ascNode']);
        self::assertEqualsWithDelta(Angle::degnorm($meanNode[0] + 180.0), $result['descNode'][0], 1e-12);
        self::assertSame(0.0, $result['descNode'][1]);

        self::assertSame($meanApogee, $result['aphelion']);
        self::assertEqualsWithDelta(Angle::degnorm($meanApogee[0] + 180.0), $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(-$meanApogee[1], $result['perihelion'][1], 1e-12);
    }

    public function testMoonOsculatingNodesAndApsidesUseExistingLunarPoints(): void
    {
        $tjdEt = 2451545.000738760;

        $result = NodesApsides::nodAps(
            $tjdEt,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertSame(TrueNode::apparent($tjdEt), $result['ascNode']);
        self::assertSame(OsculatingApogee::apparent($tjdEt), $result['aphelion']);
    }

    public function testNodApsCanSkipNutation(): void
    {
        $tjdEt = 2451545.000738760;

        $result = NodesApsides::nodAps(
            $tjdEt,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame(MeanNode::geocentric($tjdEt), $result['ascNode']);
        self::assertSame(MeanApogee::geocentric($tjdEt), $result['aphelion']);
    }

    public function testNodApsUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $ut = NodesApsides::nodApsUt($tjdUt, Catalog::SE_MOON, $flags, Catalog::SE_NODBIT_MEAN);
        $et = NodesApsides::nodAps($tjdEt, Catalog::SE_MOON, $flags, Catalog::SE_NODBIT_MEAN);

        self::assertSame($et, $ut);
    }

    public function testCalculatorNodApsDelegatesToNodesApsides(): void
    {
        $expected = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_OSCU
        );

        $actual = Calculator::nodAps(
            2451545.000738760,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertSame($expected, $actual);
    }

    public function testUnsupportedBodyReturnsError(): void
    {
        $result = NodesApsides::nodAps(
            2451545.0,
            Catalog::SE_MEAN_NODE,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('not implemented', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['ascNode']);
    }

    public function testMercuryMeanPlanetaryNodesAndApsidesMatchSwissMeanElements(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT;

        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            $flags,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame(Catalog::normalizeEphemerisFlags($flags), $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(48.330893023992, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(228.330893023992, $result['descNode'][0], 1e-12);
        self::assertEqualsWithDelta(77.273956506887, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(257.273956506887, $result['aphelion'][0], 1e-12);

        self::assertEqualsWithDelta(3.402979873688, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.307498607092, $result['perihelion'][2], 1e-12);
        self::assertEqualsWithDelta(0.466698012908, $result['aphelion'][2], 1e-12);
    }

    public function testNeptuneMeanPlanetaryNodesAndApsidesMatchSwissMeanElements(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT;

        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_NEPTUNE,
            $flags,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(131.784057022293, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(311.784057022293, $result['descNode'][0], 1e-12);
        self::assertEqualsWithDelta(48.126692482312, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(228.126692482312, $result['aphelion'][0], 1e-12);

        self::assertEqualsWithDelta(-1.759124993656, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(29.839752001883, $result['perihelion'][2], 1e-12);
        self::assertEqualsWithDelta(30.381021736117, $result['aphelion'][2], 1e-12);
    }

    public function testPlanetaryMeanFocalPointReplacesAphelionDistance(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN | Catalog::SE_NODBIT_FOPOINT
        );

        self::assertEqualsWithDelta(257.273956506887, $result['aphelion'][0], 1e-12);
        self::assertEqualsWithDelta(0.159199405815, $result['aphelion'][2], 1e-12);
    }

    public function testPlanetaryOsculatingNodesAndApsidesFromCurrentMoshierOrbit(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT | Catalog::SEFLG_SWIEPH, $result['rc']);

        self::assertEqualsWithDelta(48.330747832153, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.314277007650, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(228.330747832153, $result['descNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.451906112783, $result['descNode'][2], 1e-12);

        self::assertEqualsWithDelta(77.273972445126, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(3.403050386038, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.307499322676, $result['perihelion'][2], 1e-12);

        self::assertEqualsWithDelta(257.273972445126, $result['aphelion'][0], 1e-12);
        self::assertEqualsWithDelta(-3.403050386038, $result['aphelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.466697478547, $result['aphelion'][2], 1e-12);
    }

    public function testPlanetaryOsculatingNodesCanBeReturnedGeocentrically(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertEqualsWithDelta(297.793739277820, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.828000798031, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(290.128653940251, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(0.711499494186, $result['perihelion'][2], 1e-12);
    }

    public function testPlanetaryOsculatingFocalPointReplacesAphelionDistance(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_OSCU | Catalog::SE_NODBIT_FOPOINT
        );

        self::assertEqualsWithDelta(257.273972445126, $result['aphelion'][0], 1e-12);
        self::assertEqualsWithDelta(0.159198155871, $result['aphelion'][2], 1e-12);
    }

    public function testDistantPlanetaryOsculatingNodesAreSupported(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_NEPTUNE,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertEqualsWithDelta(131.793812795747, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(30.127566752967, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(37.265722738764, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(29.767382250721, $result['perihelion'][2], 1e-12);
    }

    public function testPlanetaryMeanNodesCanBeReturnedGeocentrically(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(297.793670513443, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.000274904320, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(0.828000319857, $result['ascNode'][2], 1e-12);

        self::assertEqualsWithDelta(290.128629482986, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(1.470328521461, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.711500110715, $result['perihelion'][2], 1e-12);
    }

    public function testPlanetaryMeanGeocentricAndHeliocentricNodesDiffer(): void
    {
        $geocentric = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        $heliocentric = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertGreaterThan(
            100.0,
            abs(Angle::difdeg2n($geocentric['ascNode'][0], $heliocentric['ascNode'][0]))
        );
    }

    public function testDistantPlanetaryMeanNodesCanBeReturnedGeocentrically(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_NEPTUNE,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(132.788048930109, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(29.243320855332, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(46.602638877131, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(29.248393365453, $result['perihelion'][2], 1e-12);
    }

    public function testPlanetaryMeanNodesApplyNutationByDefault(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(297.789772916169, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.000274904320, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(0.828000319857, $result['ascNode'][2], 1e-12);

        self::assertEqualsWithDelta(290.124731885712, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(1.470328521461, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.711500110715, $result['perihelion'][2], 1e-12);
    }

    public function testPlanetaryMeanHeliocentricNodesCanApplyNutation(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(48.326995426718, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(77.270058909614, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(3.402979873688, $result['perihelion'][1], 1e-12);
    }

    public function testPlanetaryMeanTruePositionSkipsNutation(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(297.793670513443, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(290.128629482986, $result['perihelion'][0], 1e-12);
    }

    public function testNodApsCanReturnEquatorialCoordinates(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(299.873187809427, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(-20.601856241966, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(0.828000319857, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(1.209756267726, $result['ascNode'][3], 1e-12);
        self::assertEqualsWithDelta(0.228867967273, $result['ascNode'][4], 1e-12);
    }

    public function testNodApsCanReturnCartesianCoordinates(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(0.244721701682, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(-0.667840096228, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.018256564400, $result['perihelion'][2], 1e-12);
        self::assertEqualsWithDelta(0.017207626641, $result['perihelion'][3], 1e-12);
        self::assertEqualsWithDelta(0.003157968995, $result['perihelion'][4], 1e-12);
    }

    public function testNodApsCanReturnRadians(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_RADIANS,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(5.197412016153, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.000004797986, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(0.828000319857, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(0.020163560690, $result['ascNode'][3], 1e-12);
    }

    public function testNodApsWithoutSpeedZerosSpeedComponents(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame(0.0, $result['ascNode'][3]);
        self::assertSame(0.0, $result['ascNode'][4]);
        self::assertSame(0.0, $result['ascNode'][5]);
        self::assertSame(0.0, $result['perihelion'][3]);
        self::assertSame(0.0, $result['perihelion'][4]);
        self::assertSame(0.0, $result['perihelion'][5]);
    }

    public function testNodApsCanReturnJ2000Coordinates(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT | Catalog::SEFLG_J2000,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(297.793670487528, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(0.000274899069, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(0.828000319857, $result['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(1.155284433992, $result['ascNode'][3], 1e-12);

        self::assertEqualsWithDelta(290.128629457343, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(1.470328517586, $result['perihelion'][1], 1e-12);
        self::assertEqualsWithDelta(0.711500110715, $result['perihelion'][2], 1e-12);
    }

    public function testNodApsCanReturnHeliocentricJ2000Coordinates(): void
    {
        $result = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT | Catalog::SEFLG_J2000,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertEqualsWithDelta(48.330892998078, $result['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(-0.000000007487, $result['ascNode'][1], 1e-12);
        self::assertEqualsWithDelta(77.273956480320, $result['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(3.402979871207, $result['perihelion'][1], 1e-12);
    }

    public function testPlutoMeanNodeFallsBackToOsculatingNodesAndApsides(): void
    {
        $default = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_PLUTO,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        $osculating = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_PLUTO,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_OSCU
        );

        self::assertSame($osculating, $default);

        self::assertEqualsWithDelta(110.287160139586, $default['ascNode'][0], 1e-12);
        self::assertEqualsWithDelta(40.952780225306, $default['ascNode'][2], 1e-12);
        self::assertEqualsWithDelta(225.026840396964, $default['perihelion'][0], 1e-12);
        self::assertEqualsWithDelta(29.657382441907, $default['perihelion'][2], 1e-12);
    }

    public function testPlutoAsteroidNumberMapsToMainPluto(): void
    {
        $expected = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_PLUTO,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        $actual = NodesApsides::nodAps(
            2451545.000738760,
            Catalog::SE_AST_OFFSET + 134340,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_TRUEPOS | Catalog::SEFLG_NONUT,
            Catalog::SE_NODBIT_MEAN
        );

        self::assertSame($expected, $actual);
    }
}