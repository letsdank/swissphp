<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;
use SwissEph\Ayanamsa;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\Coordinates;
use SwissEph\Crossing;
use SwissEph\DeltaT;
use SwissEph\EarthPosition;
use SwissEph\Eclipse;
use SwissEph\EclipseResult;
use SwissEph\EclipseWhenResult;
use SwissEph\MeanApogee;
use SwissEph\MeanNode;
use SwissEph\MoshierMoon;
use SwissEph\Observer;
use SwissEph\OsculatingApogee;
use SwissEph\Phenomena;
use SwissEph\Precession;
use SwissEph\SiderealTime;
use SwissEph\SolarEclipseResult;
use SwissEph\SolarEclipseWhenResult;
use SwissEph\SolarPosition;
use SwissEph\SwissDate;
use SwissEph\TrueNode;
use SwissEph\UtcTime;

final class CalculatorTest extends TestCase
{
    public function testCalculatorJuldayDelegatesToSwissDate(): void
    {
        self::assertSame(
            SwissDate::julday(2000, 1, 1, 12.0, SwissDate::GREGORIAN_CALENDAR),
            Calculator::julday(2000, 1, 1, 12.0, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testCalculatorRevjulDelegatesToSwissDate(): void
    {
        self::assertSame(
            SwissDate::revjul(2451545.0, SwissDate::GREGORIAN_CALENDAR),
            Calculator::revjul(2451545.0, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testCalculatorDateConversionDelegatesToSwissDate(): void
    {
        self::assertSame(
            SwissDate::dateConversion(2000, 1, 1, 12.0, 'g'),
            Calculator::dateConversion(2000, 1, 1, 12.0, 'g')
        );
    }

    public function testCalculatorUtcTimeZoneDelegatesToSwissDate(): void
    {
        self::assertSame(
            SwissDate::utcTimeZone(2024, 1, 1, 12, 30, 0.0, 3.0),
            Calculator::utcTimeZone(2024, 1, 1, 12, 30, 0.0, 3.0)
        );
    }

    public function testCalculatorUtcToJdDelegatesToUtcTime(): void
    {
        self::assertSame(
            UtcTime::utcToJd(2000, 1, 1, 0, 0, 0.0, SwissDate::GREGORIAN_CALENDAR),
            Calculator::utcToJd(2000, 1, 1, 0, 0, 0.0, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testCalculatorJdetToUtcDelegatesToUtcTime(): void
    {
        self::assertSame(
            UtcTime::jdetToUtc(2451544.5007428704, SwissDate::GREGORIAN_CALENDAR),
            Calculator::jdetToUtc(2451544.5007428704, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testCalculatorJdut1ToUtcDelegatesToUtcTime(): void
    {
        self::assertSame(
            UtcTime::jdut1ToUtc(2457754.4999952596, SwissDate::GREGORIAN_CALENDAR),
            Calculator::jdut1ToUtc(2457754.4999952596, SwissDate::GREGORIAN_CALENDAR)
        );
    }

    public function testCalculatorLunEclipseHowDelegatesToEclipse(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        self::assertSame(
            Eclipse::lunarHow($tjdUt),
            Calculator::lunEclipseHow($tjdUt)
        );
    }

    public function testCalculatorLunEclipseHowResultDelegatesToEclipse(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);

        $result = Calculator::lunEclipseHowResult($tjdUt);

        self::assertInstanceOf(EclipseResult::class, $result);
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(
            Eclipse::lunarHowResult($tjdUt)->umbralMagnitude(),
            $result->umbralMagnitude(),
            1e-12
        );
    }

    public function testCalculatorLunEclipseHowPassesObserver(): void
    {
        $tjdUt = SwissDate::julday(2000, 1, 21, 4.75, SwissDate::GREGORIAN_CALENDAR);
        $observer = new Observer(13.4050, 52.5200, 34.0);

        self::assertSame(
            Eclipse::lunarHow($tjdUt, Catalog::SEFLG_DEFAULTEPH, $observer),
            Calculator::lunEclipseHow($tjdUt, Catalog::SEFLG_DEFAULTEPH, $observer)
        );
    }

    public function testCalculatorLunEclipseWhenDelegatesToEclipse(): void
    {
        self::assertSame(
            Eclipse::lunarWhen(2451545.0),
            Calculator::lunEclipseWhen(2451545.0)
        );
    }

    public function testCalculatorLunEclipseWhenLocDelegatesToEclipse(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        self::assertSame(
            Eclipse::lunarWhenLoc(2451545.0, Catalog::SEFLG_DEFAULTEPH, $observer),
            Calculator::lunEclipseWhenLoc(2451545.0, $observer)
        );
    }

    public function testCalculatorLunEclipseWhenLocResultDelegatesToEclipse(): void
    {
        $result = Calculator::lunEclipseWhenLocResult(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0)
        );

        self::assertInstanceOf(EclipseWhenResult::class, $result);
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(2451564.687058892, $result->maximumTime(), 1e-9);
    }

    public function testCalculatorEclipseWhenResultDelegatesToEclipse(): void
    {
        $result = Calculator::lunEclipseWhenResult(2451545.0);

        self::assertInstanceOf(EclipseWhenResult::class, $result);
        self::assertEqualsWithDelta(
            Eclipse::lunarWhenResult(2451545.0)->maximumTime(),
            $result->maximumTime(),
            1e-12
        );
    }

    public function testCalculatorSolEclipseWhenGlobDelegatesToEclipse(): void
    {
        self::assertSame(
            Eclipse::solarWhenGlob(2460409.0),
            Calculator::solEclipseWhenGlob(2460409.0)
        );
    }

    public function testCalculatorSolEclipseWhenGlobResultDelegatesToEclipse(): void
    {
        $result = Calculator::solEclipseWhenGlobResult(2460409.0);

        self::assertInstanceOf(SolarEclipseWhenResult::class, $result);
        self::assertFalse($result->isEclipse());
        self::assertSame('global solar eclipse search is not implemented yet', $result->result->error);
    }

    public function testCalculatorSolEclipseWhereDelegatesToEclipse(): void
    {
        self::assertSame(
            Eclipse::solarWhere(2460409.222222222),
            Calculator::solEclipseWhere(2460409.222222222)
        );
    }

    public function testCalculatorSolEclipseWherePreservesNoEclipseShape(): void
    {
        $result = Calculator::solEclipseWhere(2451545.0);

        self::assertSame(0, $result['rc']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
        self::assertSame('no solar eclipse at tjd = 2451545.000000', $result['error']);
    }

    public function testCalculatorSolEclipseWhereResultDelegatesToEclipse(): void
    {
        $result = Calculator::solEclipseWhereResult(2460409.222222222);

        self::assertInstanceOf(SolarEclipseResult::class, $result);
        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isTotal());
        self::assertEqualsWithDelta(-188.1032147468757, $result->coreShadowDiameterKm(), 1e-9);
    }

    public function testCalculatorSolEclipseHowDelegatesToEclipse(): void
    {
        $observer = new Observer(-104.9903, 39.7392, 1609.0);

        self::assertSame(
            Eclipse::solarHow(2460409.224305555, $observer),
            Calculator::solEclipseHow(2460409.224305555, $observer)
        );
    }

    public function testCalculatorSolEclipseHowResultDelegatesToEclipse(): void
    {
        $result = Calculator::solEclipseHowResult(
            2460409.224305555,
            new Observer(-104.9903, 39.7392, 1609.0)
        );

        self::assertInstanceOf(SolarEclipseResult::class, $result);
        self::assertTrue($result->isEclipse());
        self::assertTrue($result->isPartial());
    }

    public function testCalculatorSolEclipseHowResultPreservesErrors(): void
    {
        $result = Calculator::solEclipseHowResult(
            2460409.25,
            new Observer(-104.9903, 39.7392, 30000.0)
        );

        self::assertInstanceOf(SolarEclipseResult::class, $result);
        self::assertFalse($result->isEclipse());
        self::assertSame(
            'location for eclipses must be between -500 and 25000 m above sea',
            $result->result->error
        );
    }

    public function testCalcSunReturnsSolarPositionWithSpeed(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        $expected = SolarPosition::position($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcSunWithoutSpeedZerosSpeedFields(): void
    {
        $result = Calculator::calc(2451545.000738760, Catalog::SE_SUN);

        self::assertSame(Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertNotSame(0.0, $result['xx'][0]);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED);

        $ut = Calculator::calcUt($tjdUt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $et = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertEqualsWithDelta($et['xx'][0], $ut['xx'][0], 1e-12);
        self::assertEqualsWithDelta($et['xx'][3], $ut['xx'][3], 1e-12);
    }

    public function testCalcMoonReturnsMoshierMoonPositionWithSpeed(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_MOON, Catalog::SEFLG_SPEED);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        $expected = MoshierMoon::geocentric($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcMoonWithoutSpeedZerosSpeedFields(): void
    {
        $result = Calculator::calc(2451545.000738760, Catalog::SE_MOON);

        self::assertSame(Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(223.290009157557506, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(5.200718152103959, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236186, $result['xx'][2], 1e-15);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcSunSiderealAppliesAyanamsa(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;

        $tropical = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $sidereal = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags, Catalog::SE_SIDM_LAHIRI);

        self::assertEqualsWithDelta(
            Angle::degnorm($tropical['xx'][0] - 23.85322475),
            $sidereal['xx'][0],
            5e-5
        );
    }

    public function testCalcCanReturnRadians(): void
    {
        $degrees = Calculator::calc(2451545.000738760, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $radians = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_RADIANS
        );

        self::assertEqualsWithDelta(deg2rad($degrees['xx'][0]), $radians['xx'][0], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][3]), $radians['xx'][3], 1e-12);
        self::assertEqualsWithDelta($degrees['xx'][2], $radians['xx'][2], 1e-12);
    }

    public function testUnsupportedPlanetReturnsError(): void
    {
        $result = Calculator::calc(2451545.0, Catalog::SE_INTP_APOG);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame('Unsupported planet or flag combination.', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcUserWithoutSiderealMatchesCalc(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $normal = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser($tjdEt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($normal['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserSunSiderealUsesUserAyanamsa(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $result = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            $sidMode,
            2374717.0,
            30.0
        );

        $expected = Ayanamsa::userSiderealPosition(
            SolarPosition::position($tjdEt),
            $tjdEt,
            $sidMode,
            2374717.0,
            30.0
        );

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta($expected[0], $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta($expected[3], $result['xx'][3], 1e-12);
    }

    public function testCalcUserUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $et = Calculator::calcUser($tjdEt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0);
        $ut = Calculator::calcUserUt($tjdUt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0);

        self::assertEqualsWithDelta($et['xx'][0], $ut['xx'][0], 1e-12);
        self::assertEqualsWithDelta($et['xx'][3], $ut['xx'][3], 1e-12);
    }

    public function testCalcUserCanReturnRadians(): void
    {
        $tjdEt = 2451545.000738760;
        $sidMode = Catalog::SE_SIDM_USER;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;

        $degrees = Calculator::calcUser($tjdEt, Catalog::SE_SUN, $flags, $sidMode, 2451545.0, 30.0);
        $radians = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags | Catalog::SEFLG_RADIANS,
            $sidMode,
            2451545.0,
            30.0
        );

        self::assertEqualsWithDelta(deg2rad($degrees['xx'][0]), $radians['xx'][0], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][3]), $radians['xx'][3], 1e-12);
        self::assertEqualsWithDelta($degrees['xx'][2], $radians['xx'][2], 1e-12);
    }

    public function testCalcSunCanReturnEquatorialCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(281.288848500616552, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-23.031676915446738, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.104326899069435, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(0.079365588729827, $result['xx'][4], 1e-12);
    }

    public function testCalcEquatorialUsesCoordinateTransform(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL;

        $result = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);

        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];
        $expected = Coordinates::cotransSp(SolarPosition::position($tjdEt), -$eps);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcEquatorialRadiansAreConvertedAfterTransform(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL;

        $degrees = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $radians = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags | Catalog::SEFLG_RADIANS);

        self::assertEqualsWithDelta(deg2rad($degrees['xx'][0]), $radians['xx'][0], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][1]), $radians['xx'][1], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][3]), $radians['xx'][3], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][4]), $radians['xx'][4], 1e-12);
        self::assertEqualsWithDelta($degrees['xx'][2], $radians['xx'][2], 1e-12);
    }

    public function testCalcUserEquatorialMatchesRegularCalcWithoutSidereal(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcSunCanReturnCartesianCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        // Swiss Ephemeris fixture, Moshier fallback:
        // x 0.176984773, y -0.967269150, z 0.000003903
        // dx 0.017208796, dy 0.003156229, dz -0.000000010.
        self::assertEqualsWithDelta(0.177147825515689, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.967239321452056, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.000003972733536, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(0.017207581796928, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(0.003159011370005, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(-0.000000109774078, $result['xx'][5], 1e-12);
    }

    public function testCalcCartesianUsesCoordinateTransform(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        $position = SolarPosition::position($tjdEt);
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        $expected = Coordinates::polcartSp($position);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcCartesianRadiansFlagDoesNotChangeCartesianCoordinates(): void
    {
        $cartesian = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        $cartesianWithRadians = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ | Catalog::SEFLG_RADIANS
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($cartesian['xx'][$i], $cartesianWithRadians['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserCartesianMatchesRegularCalcWithoutSidereal(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcEquatorialNonutUsesMeanObliquity(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_NONUT;

        $result = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);

        $expected = Coordinates::cotransSp(
            SolarPosition::position($tjdEt),
            -SiderealTime::meanObliquity($tjdEt)
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcEquatorialNutationChangesDeclination(): void
    {
        $tjdEt = 2451545.000738760;

        $true = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        $mean = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_NONUT
        );

        self::assertNotEqualsWithDelta($true['xx'][1], $mean['xx'][1], 1e-7);
    }

    public function testCalcUserEquatorialNonutUsesMeanObliquity(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_NONUT;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcSunCanReturnJ2000Coordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        // Swiss Ephemeris fixture, Moshier fallback:
        // longitude 280.3727885, latitude 0.0002274, longitude speed 1.0193939.
        self::assertEqualsWithDelta(280.3727885, $result['xx'][0], 6e-3);
        self::assertEqualsWithDelta(0.0002274, $result['xx'][1], 3e-4);
        self::assertEqualsWithDelta(1.0193939, $result['xx'][3], 5e-4);
    }

    public function testCalcJ2000MatchesPrecessionTransform(): void
    {
        $tjdEt = 2451545.000738760;

        $result = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000
        );

        $position = SolarPosition::position($tjdEt);
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        $cartesian = Coordinates::polcartSp($position);

        $precessedPosition = Precession::precess(
            [$cartesian[0], $cartesian[1], $cartesian[2]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $precessedSpeed = Precession::precess(
            [$cartesian[3], $cartesian[4], $cartesian[5]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $expected = Coordinates::cartpolSp([
            $precessedPosition[0],
            $precessedPosition[1],
            $precessedPosition[2],
            $precessedSpeed[0],
            $precessedSpeed[1],
            $precessedSpeed[2],
        ]);

        self::assertEqualsWithDelta(rad2deg($expected[0]), $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[1]), $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta($expected[2], $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[3]), $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[4]), $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta($expected[5], $result['xx'][5], 1e-12);
    }

    public function testCalcUserJ2000MatchesRegularCalcWithoutSidereal(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_J2000;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcSunCanReturnEquatorialCartesianCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ
        );
        self::assertEqualsWithDelta(0.177147825515688, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.887437132033675, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(-0.384717093026373, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(0.017207581796928, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(0.002898415282240, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.001256400294314, $result['xx'][5], 1e-12);
    }

    public function testCalcEquatorialCartesianUsesEquatorialTransformBeforeCartesianTransform(): void
    {
        $tjdEt = 2451545.000738760;

        $result = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ
        );

        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];

        $expected = Coordinates::cotransSp(SolarPosition::position($tjdEt), -$eps);
        $expected[0] = deg2rad($expected[0]);
        $expected[1] = deg2rad($expected[1]);
        $expected[3] = deg2rad($expected[3]);
        $expected[4] = deg2rad($expected[4]);
        $expected = Coordinates::polcartSp($expected);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcEquatorialCartesianRadiansFlagDoesNotChangeCartesianCoordinates(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ;

        $cartesian = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags);
        $withRadians = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags | Catalog::SEFLG_RADIANS);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($cartesian['xx'][$i], $withRadians['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserEquatorialCartesianMatchesRegularCalcWithoutSidereal(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcCartesianWithoutSpeedZerosVelocityComponents(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_XYZ
        );

        self::assertNotSame(0.0, $result['xx'][0]);
        self::assertNotSame(0.0, $result['xx'][1]);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcEquatorialCartesianWithoutSpeedZerosVelocityComponents(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ
        );

        self::assertNotSame(0.0, $result['xx'][0]);
        self::assertNotSame(0.0, $result['xx'][1]);
        self::assertNotSame(0.0, $result['xx'][2]);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcUserCartesianWithoutSpeedZerosVelocityComponents(): void
    {
        $result = Calculator::calcUser(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_XYZ,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertNotSame(0.0, $result['xx'][0]);
        self::assertNotSame(0.0, $result['xx'][1]);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcSiderealNonutUsesMeanAyanamsa(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL | Catalog::SEFLG_NONUT;

        $tropical = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $sidereal = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags, Catalog::SE_SIDM_LAHIRI);

        self::assertEqualsWithDelta(
            Angle::degnorm($tropical['xx'][0] - Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI, false)),
            $sidereal['xx'][0],
            1e-12
        );

        self::assertEqualsWithDelta(
            $tropical['xx'][3] - Ayanamsa::ayanamsaWithSpeed($tjdEt, Catalog::SE_SIDM_LAHIRI, false)[1],
            $sidereal['xx'][3],
            1e-12
        );
    }

    public function testCalcSiderealNutationChangesLongitude(): void
    {
        $tjdEt = 2451545.000738760;

        $apparent = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            Catalog::SE_SIDM_LAHIRI
        );

        $mean = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL | Catalog::SEFLG_NONUT,
            Catalog::SE_SIDM_LAHIRI
        );

        self::assertGreaterThan(1e-4, abs(Angle::difdeg2n($apparent['xx'][0], $mean['xx'][0])));
    }

    public function testCalcUserSiderealNonutUsesMeanUserAyanamsa(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL | Catalog::SEFLG_NONUT;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $tropical = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $sidereal = Calculator::calcUser($tjdEt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0);

        self::assertEqualsWithDelta(
            Angle::degnorm($tropical['xx'][0] - Ayanamsa::userAyanamsa($tjdEt, $sidMode, 2374717.0, 30.0, false)),
            $sidereal['xx'][0],
            1e-12
        );

        self::assertEqualsWithDelta(
            $tropical['xx'][3] - Ayanamsa::userAyanamsaWithSpeed($tjdEt, $sidMode, 2374717.0, 30.0, false)[1],
            $sidereal['xx'][3],
            1e-12
        );
    }

    public function testCalcEarthHeliocentricReturnsMoshierEarthPosition(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR;

        $result = Calculator::calc($tjdEt, Catalog::SE_EARTH, $flags);
        $expected = EarthPosition::heliocentric($tjdEt);

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcEarthHeliocentricIsCloseToSwissEphemerisFixture(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR
        );

        // Moshier Earth table with EMB -> Earth correction.
        self::assertEqualsWithDelta(100.378576987452021, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.000231480184610, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818223, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.019393782669908, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(0.000006394499740, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(-0.000007356974807, $result['xx'][5], 1e-12);
    }

    public function testCalcEarthWithoutHeliocentricFlagReturnsError(): void
    {
        $result = Calculator::calc(2451545.000738760, Catalog::SE_EARTH, Catalog::SEFLG_SPEED);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcEarthHeliocentricCanReturnCartesianCoordinates(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_XYZ;

        $result = Calculator::calc($tjdEt, Catalog::SE_EARTH, $flags);

        $expected = EarthPosition::heliocentric($tjdEt);
        $expected[0] = deg2rad($expected[0]);
        $expected[1] = deg2rad($expected[1]);
        $expected[3] = deg2rad($expected[3]);
        $expected[4] = deg2rad($expected[4]);
        $expected = Coordinates::polcartSp($expected);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcSunHeliocentricReturnsZeroVector(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR;

        $result = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags);

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcSunHeliocentricCartesianReturnsZeroVector(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_XYZ;

        $result = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags);

        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcSunBarycentricReturnsZeroVectorInCurrentSubset(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_BARYCTR;

        $result = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags);

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcEarthBarycentricFallsBackToHeliocentricInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;

        $barycentric = Calculator::calc(
            $tjdEt,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_BARYCTR
        );

        $heliocentric = Calculator::calc(
            $tjdEt,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($heliocentric['xx'][$i], $barycentric['xx'][$i], 1e-12);
        }
    }

    public function testCalcPreservesTruePositionFlagInReturnCode(): void
    {
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS;

        $result = Calculator::calc(2451545.000738760, Catalog::SE_SUN, $flags);

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);
    }

    public function testCalcAstrometricFlagsAreAcceptedInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $astrometric = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ASTROMETRIC
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ASTROMETRIC | Catalog::SEFLG_SWIEPH,
            $astrometric['rc']
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $astrometric['xx'][$i], 1e-12);
        }
    }

    public function testCalcNoAberrationAndNoGravDeflectionFlagsAreAcceptedSeparately(): void
    {
        $tjdEt = 2451545.000738760;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_NOABERR | Catalog::SEFLG_NOGDEFL;
        $result = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserPreservesAstrometricFlags(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_ASTROMETRIC;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $user = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame($regular['rc'], $user['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcTopoAppliesObserverCorrection(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED;
        $observer = new Observer(30.0, 59.0, 120.0);

        $geo = Calculator::calc($tjdEt, Catalog::SE_SUN, $flags);
        $topo = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, $flags, $observer);

        self::assertSame($flags | Catalog::SEFLG_TOPOCTR | Catalog::SEFLG_SWIEPH, $topo['rc']);
        self::assertNotEqualsWithDelta($geo['xx'][0], $topo['xx'][0], 1e-5);
        self::assertNotEqualsWithDelta($geo['xx'][1], $topo['xx'][1], 1e-5);
    }

    public function testCalcTopoUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED | Catalog::SEFLG_TOPOCTR);
        $flags = Catalog::SEFLG_SPEED;
        $observer = new Observer(30.0, 59.0, 120.0);

        $et = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, $flags, $observer);
        $ut = Calculator::calcTopoUt($tjdUt, Catalog::SE_SUN, $flags, $observer);

        self::assertSame($et['rc'], $ut['rc']);
        self::assertEqualsWithDelta($et['xx'][0], $ut['xx'][0], 1e-12);
        self::assertEqualsWithDelta($et['xx'][3], $ut['xx'][3], 1e-12);
    }

    public function testCalcTopoCanUseSiderealMode(): void
    {
        $tjdEt = 2451545.000738760;
        $observer = new Observer(30.0, 59.0, 120.0);

        $tropical = Calculator::calcTopo(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            $observer
        );

        $sidereal = Calculator::calcTopo(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            $observer,
            Catalog::SE_SIDM_LAHIRI
        );

        $expected = Ayanamsa::siderealPosition(
            $tropical['xx'],
            $tjdEt,
            Catalog::SE_SIDM_LAHIRI
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL | Catalog::SEFLG_TOPOCTR | Catalog::SEFLG_SWIEPH,
            $sidereal['rc']
        );

        self::assertEqualsWithDelta($expected[0], $sidereal['xx'][0], 1e-12);
        self::assertEqualsWithDelta($expected[3], $sidereal['xx'][3], 1e-12);
    }

    public function testCalcTopoIsCloseToSwissEphemerisFixture(): void
    {
        $topo = Calculator::calcTopo(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            new Observer(30.0, 59.0, 120.0)
        );

        self::assertEqualsWithDelta(280.377934125141337, $topo['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.001888968011110, $topo['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.983308596358414, $topo['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.012420413373276, $topo['xx'][3], 1e-10);
    }

    public function testCalcTopoMatchesManualTopocentricCorrection(): void
    {
        $tjdEt = 2451545.000738760;
        $observer = new Observer(30.0, 59.0, 120.0);

        $topo = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);

        $cartesian = SolarPosition::position($tjdEt);
        $cartesian[0] = deg2rad($cartesian[0]);
        $cartesian[1] = deg2rad($cartesian[1]);
        $cartesian[3] = deg2rad($cartesian[3]);
        $cartesian[4] = deg2rad($cartesian[4]);
        $cartesian = Coordinates::polcartSp($cartesian);

        $observerVector = $observer->geocentricVector($tjdEt);

        for ($i = 0; $i <= 5; $i++) {
            $cartesian[$i] -= $observerVector[$i];
        }

        $expected = Coordinates::cartpolSp($cartesian);

        self::assertEqualsWithDelta(rad2deg($expected[0]), $topo['xx'][0], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[1]), $topo['xx'][1], 1e-12);
        self::assertEqualsWithDelta($expected[2], $topo['xx'][2], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[3]), $topo['xx'][3], 1e-12);
        self::assertEqualsWithDelta(rad2deg($expected[4]), $topo['xx'][4], 1e-12);
        self::assertEqualsWithDelta($expected[5], $topo['xx'][5], 1e-12);
    }

    public function testCalcUserTopoWithoutSiderealMatchesCalcTopo(): void
    {
        $tjdEt = 2451545.000738760;
        $observer = new Observer(30.0, 59.0, 120.0);
        $flags = Catalog::SEFLG_SPEED;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $topo = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, $flags, $observer);
        $userTopo = Calculator::calcUserTopo(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        self::assertSame($topo['rc'], $userTopo['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($topo['xx'][$i], $userTopo['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserTopoSiderealUsesUserAyanamsaAfterTopocentricCorrection(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED | Catalog::SEFLG_TOPOCTR);
        $observer = new Observer(30.0, 59.0, 120.0);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $topo = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);

        $userTopo = Calculator::calcUserTopo(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        $expected = Ayanamsa::userSiderealPosition(
            $topo['xx'],
            $tjdEt,
            $sidMode,
            2374717.0,
            30.0
        );

        self::assertSame(
            $flags | Catalog::SEFLG_TOPOCTR | Catalog::SEFLG_SWIEPH,
            $userTopo['rc']
        );
        self::assertEqualsWithDelta($expected[0], $userTopo['xx'][0], 1e-12);
        self::assertEqualsWithDelta($expected[3], $userTopo['xx'][3], 1e-12);
    }

    public function testCalcUserTopoUtConvertsUtToEt(): void
    {
        $tjdUt = 2341500.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SPEED | Catalog::SEFLG_TOPOCTR);
        $observer = new Observer(30.0, 59.0, 120.0);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $et = Calculator::calcUserTopo($tjdEt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0, $observer);
        $ut = Calculator::calcUserTopoUt($tjdUt, Catalog::SE_SUN, $flags, $sidMode, 2374717.0, 30.0, $observer);

        self::assertSame($et['rc'], $ut['rc']);
        self::assertEqualsWithDelta($et['xx'][0], $ut['xx'][0], 1e-12);
        self::assertEqualsWithDelta($et['xx'][3], $ut['xx'][3], 1e-12);
    }

    public function testCalcUserTopoCanReturnEquatorialCartesian(): void
    {
        $tjdEt = 2451545.000738760;
        $observer = new Observer(30.0, 59.0, 120.0);
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL | Catalog::SEFLG_XYZ;

        $topo = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, $flags, $observer);
        $userTopo = Calculator::calcUserTopo(
            $tjdEt,
            Catalog::SE_SUN,
            $flags,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0,
            $observer
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($topo['xx'][$i], $userTopo['xx'][$i], 1e-12);
        }
    }

    public function testCalcEarthGeocentricCartesianReturnsZeroVector(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcEarthGeocentricSiderealReturnsZeroVector(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            Catalog::SE_SIDM_LAHIRI
        );

        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcAcceptsCenterBodyFlagForCurrentBodies(): void
    {
        $tjdEt = 2451545.000738760;

        $regular = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $centerBody = Calculator::calc(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_CENTER_BODY
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH,
            $centerBody['rc']
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $centerBody['xx'][$i], 1e-12);
        }
    }

    public function testCalcEarthHeliocentricIgnoresCenterBodyFlagInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;

        $regular = Calculator::calc($tjdEt, Catalog::SE_EARTH, Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR);
        $centerBody = Calculator::calc(
            $tjdEt,
            Catalog::SE_EARTH,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR, Catalog::SEFLG_CENTER_BODY
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_SWIEPH,
            $centerBody['rc']
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($regular['xx'][$i], $centerBody['xx'][$i], 1e-12);
        }
    }

    public function testCalcDropsUnsupportedCenterBodyFlagFromReturnCode(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_CENTER_BODY
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
    }

    public function testCalcUserDropsUnsupportedCenterBodyFlagFromReturnCode(): void
    {
        $result = Calculator::calcUser(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_CENTER_BODY,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
    }

    public function testCalcTopoDropsUnsupportedCenterBodyFlagFromReturnCode(): void
    {
        $result = Calculator::calcTopo(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_CENTER_BODY,
            new Observer(30.0, 59.0, 120.0)
        );

        self::assertSame(
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TOPOCTR | Catalog::SEFLG_SWIEPH,
            $result['rc']
        );
    }

    public function testCalcSpeed3RequestsSpeedInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;

        $speed = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $speed3 = Calculator::calc($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED3);

        self::assertSame(Catalog::SEFLG_SPEED3 | Catalog::SEFLG_SWIEPH, $speed3['rc']);
        self::assertNotSame(0.0, $speed3['xx'][3]);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($speed['xx'][$i], $speed3['xx'][$i], 1e-12);
        }
    }

    public function testCalcUserSpeed3RequestsSpeedInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;

        $speed = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        $speed3 = Calculator::calcUser(
            $tjdEt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED3,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame(Catalog::SEFLG_SPEED3 | Catalog::SEFLG_SWIEPH, $speed3['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($speed['xx'][$i], $speed3['xx'][$i], 1e-12);
        }
    }

    public function testCalcTopoSpeed3RequestsSpeedInCurrentSubset(): void
    {
        $tjdEt = 2451545.000738760;
        $observer = new Observer(30.0, 59.0, 120.0);

        $speed = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);
        $speed3 = Calculator::calcTopo($tjdEt, Catalog::SE_SUN, Catalog::SEFLG_SPEED3, $observer);

        self::assertSame(
            Catalog::SEFLG_SPEED3 | Catalog::SEFLG_TOPOCTR | Catalog::SEFLG_SWIEPH,
            $speed3['rc']
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($speed['xx'][$i], $speed3['xx'][$i], 1e-12);
        }
    }

    public function testCalcResultWrapsArrayResult(): void
    {
        $array = Calculator::calc(2451545.000738760, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $result = Calculator::calcResult(2451545.000738760, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame($array, $result->toArray());
        self::assertSame($array['xx'][0], $result->longitude());
        self::assertSame($array['xx'][3], $result->longitudeSpeed());
    }

    public function testCalcUtResultWrapsArrayResult(): void
    {
        $array = Calculator::calcUt(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $result = Calculator::calcUtResult(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame($array, $result->toArray());
    }

    public function testCalcUserResultWrapsArrayResult(): void
    {
        $array = Calculator::calcUser(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        $result = Calculator::calcUserResult(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcUserUtResultWrapsArrayResult(): void
    {
        $array = Calculator::calcUserUt(
            2451545.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        $result = Calculator::calcUserUtResult(
            2451545.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcTopoResultWrapsArrayResult(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);

        $array = Calculator::calcTopo(2451545.000738760, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);
        $result = Calculator::calcTopoResult(2451545.000738760, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);

        self::assertSame($array, $result->toArray());
    }


    public function testCalcTopoUtResultWrapsArrayResult(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);

        $array = Calculator::calcTopoUt(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);
        $result = Calculator::calcTopoUtResult(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED, $observer);

        self::assertSame($array, $result->toArray());
    }

    public function testCalcUserTopoResultWrapsArrayResult(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $array = Calculator::calcUserTopo(
            2341500.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        $result = Calculator::calcUserTopoResult(
            2341500.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcUserTopoUtResultWrapsArrayResult(): void
    {
        $observer = new Observer(30.0, 59.0, 120.0);
        $sidMode = Catalog::SE_SIDM_USER + Catalog::SE_SIDBIT_USER_UT;

        $array = Calculator::calcUserTopoUt(
            2341500.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        $result = Calculator::calcUserTopoUtResult(
            2341500.0,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED,
            $sidMode,
            2374717.0,
            30.0,
            $observer
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcMercuryGeocentricUsesMoshierPlanetPosition(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(271.893143606859724, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556221870023774, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097502946397082, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617585896809, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryHeliocentricUsesMoshierPlanetPosition(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR
        );

        self::assertEqualsWithDelta(253.784949016481647, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-3.022999490954366, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.466471713567305, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(2.744973145183849, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.303700239872917, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.000355086872295, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryCanReturnEquatorialCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        self::assertEqualsWithDelta(272.078890642666181, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-24.418846147173841, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.710214925220719, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.075038629739887, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617585896809, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryCanReturnCartesianCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        self::assertEqualsWithDelta(0.046753801676442, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-1.414483600862964, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(-0.024575565821032, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(0.038570205675796, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(-0.003302682137816, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(-0.002488579551273, $result['xx'][5], 1e-12);
    }

    public function testCalcVenusCanReturnSiderealCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_VENUS,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            Catalog::SE_SIDM_LAHIRI
        );

        self::assertEqualsWithDelta(217.716471883164530, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(2.066343517808433, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.137579372876022, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.208917387417013, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.028076673708714, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.006484913369748, $result['xx'][5], 1e-12);
    }

    public function testCalcMarsCanReturnTopocentricCoordinates(): void
    {
        $result = Calculator::calcTopo(
            2451545.000738760,
            Catalog::SE_MARS,
            Catalog::SEFLG_SPEED,
            new Observer(30.0, 59.0, 120.0)
        );

        self::assertEqualsWithDelta(327.967386401601232, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-1.068918177435754, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.849667114894245, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(0.771632489498321, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(0.012450746900898, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.005380721010181, $result['xx'][5], 1e-12);
    }

    public function testCalcJupiterHeliocentricCanReturnCartesianCoordinates(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_JUPITER,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR | Catalog::SEFLG_XYZ
        );

        self::assertEqualsWithDelta(4.001181376911269, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(2.938579372318278, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(-0.101783630015179, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(-0.004568302176472, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(0.006443229859664, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.000075580958729, $result['xx'][5], 1e-12);
    }

    public function testCalcSaturnCanReturnRadians(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_SATURN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_RADIANS
        );

        self::assertEqualsWithDelta(0.705104409922493, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.042670104062301, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(8.652795972650864, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(-0.000347212904550, $result['xx'][3], 1e-12);
        self::assertEqualsWithDelta(0.000082886551897, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.014387127108162, $result['xx'][5], 1e-12);
    }

    public function testCalcSunOutsideMoshierPlanetRangeReturnsError(): void
    {
        $result = Calculator::calc(624999.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
        self::assertSame(
            'jd 624999.000000 outside Moshier planet range 625000.50 .. 2818000.50',
            $result['error']
        );
    }

    public function testCalcPlanetOutsideMoshierPlanetRangeReturnsError(): void
    {
        $result = Calculator::calc(2818001.0, Catalog::SE_MERCURY, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
        self::assertSame(
            'jd 2818001.000000 outside Moshier planet range 625000.50 .. 2818000.50',
            $result['error']
        );
    }

    public function testCalcMoonOutsideMoshierMoonRangeReturnsError(): void
    {
        $result = Calculator::calc(-3100016.0, Catalog::SE_MOON, Catalog::SEFLG_SPEED);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
        self::assertSame(
            'jd -3100016.000000 outside Moshier Moon range -3100015.50 .. 8000016.50',
            $result['error']
        );
    }

    public function testCalcMercuryTruePositionSkipsLightTime(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS
        );

        self::assertEqualsWithDelta(271.905871364797406, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.995623256316812, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415528303685689, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556298504995407, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097475371004062, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004611752572531, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryNoAberrationSkipsAnnualAberration(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NOABERR
        );

        self::assertEqualsWithDelta(271.898870148325443, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994841719380015, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556245303687880, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097501703415915, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617585899393, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryNoGravDeflectionSkipsLightDeflection(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NOGDEFL
        );

        self::assertEqualsWithDelta(271.893148408144725, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826255027362, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556222172304121, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097502820742271, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617586191463, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryNoAberrationKeepsLightDeflection(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NOABERR
        );

        self::assertEqualsWithDelta(271.898870148325443, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994841719380015, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556245303687880, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097501703415915, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617585899393, $result['xx'][5], 1e-12);
    }

    public function testCalcMercuryAstrometricSkipsAberrationAndDeflection(): void
    {
        $result = Calculator::calc(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ASTROMETRIC
        );

        self::assertEqualsWithDelta(271.898874949681272, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994841160183166, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $result['xx'][2], 1e-12);
        self::assertEqualsWithDelta(1.556245605965642, $result['xx'][3], 1e-10);
        self::assertEqualsWithDelta(-0.097501577762105, $result['xx'][4], 1e-12);
        self::assertEqualsWithDelta(0.004617586192695, $result['xx'][5], 1e-12);
    }

    public function testCalcApparentReturnsSunApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_SUN);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(280.368890021761956, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.000231516499242, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818224, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentReturnsPlanetApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_MERCURY);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);

        self::assertEqualsWithDelta(271.889246009586032, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentCanDisableCorrectionLayers(): void
    {
        $result = Calculator::calcApparent(
            2451545.000738760,
            Catalog::SE_MERCURY,
            false,
            true,
            false
        );

        self::assertEqualsWithDelta(271.898870148325443, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994841719380015, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = Calculator::calcApparent($tjdEt, Catalog::SE_MERCURY);
        $ut = Calculator::calcApparentUt($tjdUt, Catalog::SE_MERCURY);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et['xx'][$i], $ut['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentUnsupportedPlanetReturnsError(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_INTP_APOG);

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame('Unsupported planet or flag combination.', $result['error']);
        self::assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $result['xx']);
    }

    public function testCalcApparentResultWrapsArrayResult(): void
    {
        $array = Calculator::calcApparent(2451545.000738760, Catalog::SE_MERCURY);
        $result = Calculator::calcApparentResult(2451545.000738760, Catalog::SE_MERCURY);

        self::assertSame($array, $result->toArray());
    }

    public function testCalcApparentFlagsUsesDefaultApparentPosition(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(271.889246009586032, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsNonutSkipsNutation(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertEqualsWithDelta(271.893143606859724, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsAstrometricSkipsDeflectionAndAberration(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_ASTROMETRIC
        );

        self::assertEqualsWithDelta(271.894977352407579, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994841160183166, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsTrueposReturnsGeometricPosition(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS
        );

        self::assertEqualsWithDelta(271.905871364797406, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.995623256316812, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415528303685689, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsSunTrueposReturnsGeometricSun(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS
        );

        self::assertEqualsWithDelta(280.378576987451993, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.000231480184610, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.983327644818223, $result['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $et = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MERCURY, $flags);
        $ut = Calculator::calcApparentFlagsUt($tjdUt, Catalog::SE_MERCURY, $flags);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et['xx'][$i], $ut['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentFlagsResultWrapsArrayResult(): void
    {
        $array = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        $result = Calculator::calcApparentFlagsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcApparentFlagsWithoutSpeedZerosSpeedFiels(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SWIEPH
        );

        self::assertEqualsWithDelta(271.889246009586032, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $result['xx'][2], 1e-12);
        self::assertSame(0.0, $result['xx'][3]);
        self::assertSame(0.0, $result['xx'][4]);
        self::assertSame(0.0, $result['xx'][5]);
    }

    public function testCalcApparentFlagsCanReturnRadians(): void
    {
        $degrees = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $radians = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_RADIANS
        );

        self::assertEqualsWithDelta(deg2rad($degrees['xx'][0]), $radians['xx'][0], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][1]), $radians['xx'][1], 1e-12);
        self::assertEqualsWithDelta($degrees['xx'][2], $radians['xx'][2], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][3]), $radians['xx'][3], 1e-12);
        self::assertEqualsWithDelta(deg2rad($degrees['xx'][4]), $radians['xx'][4], 1e-12);
        self::assertEqualsWithDelta($degrees['xx'][5], $radians['xx'][5], 1e-12);
    }

    public function testCalcApparentFlagsCanReturnCartesianCoordinates(): void
    {
        $polar = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $cartesian = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_XYZ
        );

        $expected = $polar['xx'];
        $expected[0] = deg2rad($expected[0]);
        $expected[1] = deg2rad($expected[1]);
        $expected[3] = deg2rad($expected[3]);
        $expected[4] = deg2rad($expected[4]);
        $expected = Coordinates::polcartSp($expected);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $cartesian['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentFlagsCanReturnEquatorialCoordinates(): void
    {
        $tjdEt = 2451545.000738760;

        $polar = Calculator::calcApparentFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $equatorial = Calculator::calcApparentFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_EQUATORIAL
        );

        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];
        $expected = Coordinates::cotransSp($polar['xx'], -$eps);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $equatorial['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentFlagsCanReturnSiderealCoordinates(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;

        $tropical = Calculator::calcApparentFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $sidereal = Calculator::calcApparentFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            $flags,
            Catalog::SE_SIDM_LAHIRI
        );

        self::assertEqualsWithDelta(
            Angle::degnorm($tropical['xx'][0] - Ayanamsa::ayanamsa($tjdEt, Catalog::SE_SIDM_LAHIRI)),
            $sidereal['xx'][0],
            1e-12
        );

        self::assertEqualsWithDelta($tropical['xx'][1], $sidereal['xx'][1], 1e-12);
        self::assertEqualsWithDelta($tropical['xx'][2], $sidereal['xx'][2], 1e-12);
    }

    public function testCalcApparentFlagsSiderealUtPassesSiderealMode(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $et = Calculator::calcApparentFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            $flags,
            Catalog::SE_SIDM_LAHIRI
        );

        $ut = Calculator::calcApparentFlagsUt(
            $tjdUt,
            Catalog::SE_MERCURY,
            $flags,
            Catalog::SE_SIDM_LAHIRI
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et['xx'][$i], $ut['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentUserFlagsWithoutSiderealMatchesApparentFlags(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT;
        $sidMode = Catalog::SE_SIDM_USER;

        $normal = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MERCURY, $flags);
        $user = Calculator::calcApparentUserFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            $flags,
            $sidMode,
            2451545.0,
            30.0
        );

        self::assertSame($normal['rc'], $user['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($normal['xx'][$i], $user['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentUserFlagsUsesUserAyanamsa(): void
    {
        $tjdEt = 2451545.000738760;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL;
        $sidMode = Catalog::SE_SIDM_USER;
        $t0 = 2451545.0;
        $ayanT0 = 30.0;

        $tropical = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MERCURY, Catalog::SEFLG_SPEED);
        $sidereal = Calculator::calcApparentUserFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            $flags,
            $sidMode,
            $t0,
            $ayanT0
        );

        $expected = Ayanamsa::userSiderealPosition(
            $tropical['xx'],
            $tjdEt,
            $sidMode,
            $t0,
            $ayanT0
        );

        self::assertSame($flags | Catalog::SEFLG_SWIEPH, $sidereal['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $sidereal['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentUserFlagsUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $flags = Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL | Catalog::SEFLG_NONUT;
        $sidMode = Catalog::SE_SIDM_USER;
        $t0 = 2451545.0;
        $ayanT0 = 30.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);

        $et = Calculator::calcApparentUserFlags(
            $tjdEt,
            Catalog::SE_MERCURY,
            $flags,
            $sidMode,
            $t0,
            $ayanT0
        );

        $ut = Calculator::calcApparentUserFlagsUt(
            $tjdUt,
            Catalog::SE_MERCURY,
            $flags,
            $sidMode,
            $t0,
            $ayanT0
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et['xx'][$i], $ut['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentUserFlagsResultWrapsArrayResult(): void
    {
        $array = Calculator::calcApparentUserFlags(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        $result = Calculator::calcApparentUserFlagsResult(
            2451545.000738760,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_SIDEREAL,
            Catalog::SE_SIDM_USER,
            2451545.0,
            30.0
        );

        self::assertSame($array, $result->toArray());
    }

    public function testCalcApparentReturnsMoonApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_MOON);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertSame('', $result['error']);

        self::assertEqualsWithDelta(223.282955136715003, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(5.200277522346328, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236, $result['xx'][2], 1e-15);
    }

    public function testCalcApparentFlagsMoonTrueposReturnsGeometricMoon(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MOON,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_TRUEPOS
        );

        self::assertEqualsWithDelta(223.290009157557506, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(5.200718152103959, $result['xx'][1], 1e-12);
        self::assertEqualsWithDelta(0.002690728315236186, $result['xx'][2], 1e-15);
    }

    public function testCalcMeanNodeReturnsMeanNodePosition(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_MEAN_NODE, Catalog::SEFLG_SPEED);
        $expected = MeanNode::geocentric($tjdEt);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentReturnsMeanNodeApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_MEAN_NODE);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(125.040618327046815, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.052951281508753, $result['xx'][3], 1e-12);
    }

    public function testCalcApparentFlagsMeanNodeNonutSkipsNutation(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MEAN_NODE,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertEqualsWithDelta(125.044515924320507, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.052953775025344, $result['xx'][3], 1e-12);
    }

    public function testCalcMeanApogeeReturnsMeanApogeePosition(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_MEAN_APOG, Catalog::SEFLG_SPEED);
        $expected = MeanApogee::geocentric($tjdEt);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentReturnsMeanApogeeApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_MEAN_APOG);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(263.464304993936480, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.111328250591656, $result['xx'][3], 1e-12);
    }

    public function testCalcApparentFlagsMeanApogeeNonutSkipsNutation(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_MEAN_APOG,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertEqualsWithDelta(263.468202591210172, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.111325757075065, $result['xx'][3], 1e-12);
    }

    public function testCalcTrueNodeReturnsTrueNodePosition(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_TRUE_NODE, Catalog::SEFLG_SPEED);
        $expected = TrueNode::geocentric($tjdEt);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentReturnsTrueNodeApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_TRUE_NODE);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(123.819010163233742, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.396908957948252, $result['xx'][3], 1e-12);
    }

    public function testCalcApparentFlagsTrueNodeNonutSkipsNutation(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_TRUE_NODE,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertEqualsWithDelta(123.822907760507434, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(-0.396911451464843, $result['xx'][3], 1e-12);
    }

    public function testCalcOsculatingApogeeReturnsOsculatingApogeePosition(): void
    {
        $tjdEt = 2451545.000738760;
        $result = Calculator::calc($tjdEt, Catalog::SE_OSCU_APOG, Catalog::SEFLG_SPEED);
        $expected = OsculatingApogee::geocentric($tjdEt);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($expected[$i], $result['xx'][$i], 1e-12);
        }
    }

    public function testCalcApparentReturnsOsculatingApogeeApparentPosition(): void
    {
        $result = Calculator::calcApparent(2451545.000738760, Catalog::SE_OSCU_APOG);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(251.814137225863561, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.826080383453493, $result['xx'][3], 1e-12);
    }

    public function testCalcApparentFlagsOsculatingApogeeNonutSkipsNutation(): void
    {
        $result = Calculator::calcApparentFlags(
            2451545.000738760,
            Catalog::SE_OSCU_APOG,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_NONUT
        );

        self::assertEqualsWithDelta(251.818034823137253, $result['xx'][0], 1e-12);
        self::assertEqualsWithDelta(0.826077889936902, $result['xx'][3], 1e-12);
    }

    public function testPhenoDelegatesToPhenomena(): void
    {
        $expected = Phenomena::pheno(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::pheno(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testPhenoUtDelegatesToPhenomena(): void
    {
        $expected = Phenomena::phenoUt(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::phenoUt(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testPhenoResultDelegatesToPhenomena(): void
    {
        $expected = Phenomena::phenoResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::phenoResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }

    public function testPhenoUtResultDelegatesToPhenomena(): void
    {
        $expected = Phenomena::phenoUtResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        $actual = Calculator::phenoUtResult(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected->toArray(), $actual->toArray());
    }

    public function testCrossingHelpersDelegateToCrossing(): void
    {
        self::assertSame(
            Crossing::solcross(0.0, 2451545.0, Catalog::SEFLG_SPEED),
            Calculator::solcross(0.0, 2451545.0, Catalog::SEFLG_SPEED)
        );

        self::assertSame(
            Crossing::mooncross(225.0, 2451545.0, Catalog::SEFLG_SPEED),
            Calculator::mooncross(225.0, 2451545.0, Catalog::SEFLG_SPEED)
        );

        self::assertSame(
            Crossing::mooncrossNode(2451545.0, Catalog::SEFLG_SPEED),
            Calculator::mooncrossNode(2451545.0, Catalog::SEFLG_SPEED)
        );

        self::assertSame(
            Crossing::helioCross(Catalog::SE_MERCURY, 270.0, 2451545.0, Catalog::SEFLG_SPEED, 1),
            Calculator::helioCross(Catalog::SE_MERCURY, 270.0, 2451545.0, Catalog::SEFLG_SPEED, 1)
        );
    }
}