<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\Calculator;
use SwissEph\Catalog;
use SwissEph\Observer;
use SwissEph\Refraction;
use SwissEph\RiseSet;

final class RiseSetTest extends TestCase
{
    public function testNextSetForFixedEquatorialPosition(): void
    {
        $event = RiseSet::nextSet(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0),
            [0.0, 0.0, 1.0]
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.432532297447324, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(89.999999946516937, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(0.0, $event['trueAltitude'], 1e-6);
        self::assertEqualsWithDelta(0.472143938438555, $event['apparentAltitude'], 1e-9);
    }

    public function testNextRiseForFixedEquatorialPosition(): void
    {
        $event = RiseSet::nextRise(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0),
            [0.0, 0.0, 1.0]
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.931167066097260, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(269.999999977037248, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(0.0, $event['trueAltitude'], 1e-6);
        self::assertEqualsWithDelta(0.472143888033831, $event['apparentAltitude'], 1e-9);
    }

    public function testReturnsNullWhenNoCrossingIsFound(): void
    {
        $event = RiseSet::nextSet(
            2451545.0,
            new Observer(0.0, 80.0, 0.0),
            [0.0, 80.0, 1.0],
            0.0,
            1013.2,
            15.0,
            1.0 / 48.0,
            1.0
        );

        self::assertNull($event);
    }

    public function testCustomHorizonAltitudeIsUsed(): void
    {
        $event = RiseSet::nextSet(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0),
            [0.0, 0.0, 1.0],
            -0.833
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.436324849724770, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(-0.833, $event['trueAltitude'], 1e-6);
    }

    public function testNextBodySetForSun(): void
    {
        $event = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0),
            -0.833
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.126515571027994, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(51.403360325282392, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(-0.833, $event['trueAltitude'], 1e-6);
    }

    public function testNextBodyRiseForSun(): void
    {
        $event = RiseSet::nextBodyRise(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0),
            -0.833
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.803600250743330, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(308.488110409594583, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(-0.833, $event['trueAltitude'], 1e-6);
    }

    public function testNextUpperTransitForFixedEquatorialPosition(): void
    {
        $event = RiseSet::nextUpperTransit(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0),
            [0.0, 0.0, 1.0]
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.183214910328388, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(0.0, $event['azimuth'], 1e-6);
        self::assertEqualsWithDelta(37.48, $event['trueAltitude'], 1e-12);
        self::assertEqualsWithDelta(37.500806715992404, $event['apparentAltitude'], 1e-12);
    }

    public function testNextLowerTransitForFixedEquatorialPosition(): void
    {
        $event = RiseSet::nextLowerTransit(
            2451545.0,
            new Observer(13.4050, 52.5200, 34.0),
            [0.0, 0.0, 1.0]
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.681849682703614, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(180.0, $event['azimuth'], 1e-6);
        self::assertEqualsWithDelta(-37.48, $event['trueAltitude'], 1e-12);
        self::assertEqualsWithDelta(-37.48, $event['apparentAltitude'], 1e-12);
    }

    public function testNextBodyUpperTransitForSun(): void
    {
        $event = RiseSet::nextBodyUpperTransit(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0)
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.965428228490055, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(0.022684312539070, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(14.527692382210473, $event['trueAltitude'], 1e-9);
        self::assertEqualsWithDelta(14.588238884155430, $event['apparentAltitude'], 1e-9);
    }

    public function testNextBodyLowerTransitForSun(): void
    {
        $event = RiseSet::nextBodyLowerTransit(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0)
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.465165999718010, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(179.978281566462726, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(-60.474712411361516, $event['trueAltitude'], 1e-9);
        self::assertEqualsWithDelta(-60.474712411361516, $event['apparentAltitude'], 1e-9);
    }

    public function testRiseTransDispatchesBodyRise(): void
    {
        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0),
            Catalog::SE_CALC_RISE,
            -0.833
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.803600250743330, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(308.488110409594583, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(-0.833, $event['trueAltitude'], 1e-6);
    }

    public function testRiseTransDispatchesBodySet(): void
    {
        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0),
            Catalog::SE_CALC_SET,
            -0.833
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.126515571027994, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(51.403360325282392, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(-0.833, $event['trueAltitude'], 1e-6);
    }

    public function testRiseTransDispatchesUpperTransit(): void
    {
        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, 34.0),
            Catalog::SE_CALC_MTRANSIT
        );

        self::assertNotNull($event);
        self::assertEqualsWithDelta(2451545.965428228490055, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(0.022684312539070, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(14.527692382210473, $event['trueAltitude'], 1e-9);
    }

    public function testRiseTransCivilTwilightOverridesHorizonAltitude(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_RISE | Catalog::SE_BIT_CIVIL_TWILIGHT,
            0.0
        );

        $direct = RiseSet::nextBodyRise(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            -6.0
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransDefaultsToRiseWhenNoEventTypeIsGiven(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            0
        );

        $direct = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_RISE
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransUsesApparentUpperLimbSunHorizonWhenAltitudeIsOmitted(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $horizon = -self::sunApparentRadius(2451545.0) - 34.0 / 60.0;

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransNoRefractionUsesBodySemidiameterOnly(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $horizon = -self::sunApparentRadius(2451545.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_NO_REFRACTION
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransDiscCenterNoRefractionUsesZeroHorizon(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            0.0
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransTrueHorizonAddsLocalHorizonToDefaultUpperLimb(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $trueHorizonHeight = 1.25;
        $horizon = $trueHorizonHeight - self::sunApparentRadius(2451545.0) - 34.0 / 60.0;

        $event = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET,
            $trueHorizonHeight
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransTrueHorizonCanUseDiscCenterWithoutRefraction(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $trueHorizonHeight = 1.25;

        $event = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION,
            $trueHorizonHeight
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $trueHorizonHeight
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransTrueHorizonTwilightIgnoresLocalHorizon(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_RISE | Catalog::SE_BIT_CIVIL_TWILIGHT,
            3.0
        );

        $direct = RiseSet::nextBodyRise(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            -6.0
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransFixedDiscSizeUsesMeanSunDistance(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $horizon = -rad2deg(asin(0.004650467260962157)) - 34.0 / 60.0;

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_FIXED_DISC_SIZE
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransTrueHorizonFixedDiscSizeUsesMeanSunDistance(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);
        $trueHorizonHeight = 1.25;
        $horizon = $trueHorizonHeight - rad2deg(asin(0.004650467260962157)) - 34.0 / 60.0;

        $event = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_FIXED_DISC_SIZE,
            $trueHorizonHeight
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransGeoctrNoEclLatIgnoresMoonEclipticLatitude(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_MOON,
            $observer,
            Catalog::SE_CALC_SET
            | Catalog::SE_BIT_DISC_CENTER
            | Catalog::SE_BIT_NO_REFRACTION
            | Catalog::SE_BIT_GEOCTR_NO_ECL_LAT
        );

        $withoutFlag = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_MOON,
            $observer,
            Catalog::SE_CALC_SET
            | Catalog::SE_BIT_DISC_CENTER
            | Catalog::SE_BIT_NO_REFRACTION
        );


        self::assertNotNull($event);
        self::assertNotNull($withoutFlag);
        self::assertGreaterThan(0.5, $event['tjdUt'] - $withoutFlag['tjdUt']);
        self::assertEqualsWithDelta(2451546.003509420, $event['tjdUt'], 1e-9);
        self::assertEqualsWithDelta(57.504231701645, $event['azimuth'], 1e-7);
        self::assertEqualsWithDelta(0.0, $event['trueAltitude'], 1e-6);
    }

    public function testRiseTransModifierOnlyFlagsDefaultToRise(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $event = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION
        );

        $direct = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_RISE | Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransTrueHorizonMinusHundredUsesVisibleHorizonDip(): void
    {
        $observer = new Observer(13.4050, 52.5200, 100.0);
        $horizon = 0.0001 + Refraction::horizonDip(
                $observer->altitude,
                1000.0,
                15.0
            );

        $event = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET | Catalog::SE_BIT_DISC_CENTER | Catalog::SE_BIT_NO_REFRACTION,
            -100.0,
            1000.0,
            15.0
        );

        $direct = RiseSet::nextBodySet(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            $horizon,
            1000.0,
            15.0
        );

        self::assertSame($direct, $event);
    }

    public function testRiseTransRejectsObserverBelowSwissAltitudeRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('between -500 and 25000 m above sea');

        RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, -501.0),
            Catalog::SE_CALC_RISE
        );
    }

    public function testRiseTransTrueHorizonRejectsObserverAboveSwissAltitudeRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('between -500 and 25000 m above sea');

        RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            new Observer(13.4050, 52.5200, -501.0),
            Catalog::SE_CALC_RISE,
            0.0
        );
    }

    public function testRiseTransResultWrapsArrayResult(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $array = RiseSet::riseTrans(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET
        );

        $result = RiseSet::riseTransResult(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET
        );

        self::assertNotNull($array);
        self::assertNotNull($result);
        self::assertSame($array, $result->toArray());
    }

    public function testRiseTransTrueHorizonResultWrapsArrayResult(): void
    {
        $observer = new Observer(13.4050, 52.5200, 34.0);

        $array = RiseSet::riseTransTrueHorizon(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET,
            1.0
        );

        $result = RiseSet::riseTransTrueHorizonResult(
            2451545.0,
            Catalog::SE_SUN,
            $observer,
            Catalog::SE_CALC_SET,
            1.0
        );

        self::assertNotNull($array);
        self::assertNotNull($result);
        self::assertSame($array, $result->toArray());
    }

    private static function sunApparentRadius(float $tjdUt): float
    {
        $result = Calculator::calcApparentFlagsUt(
            $tjdUt,
            Catalog::SE_SUN,
            Catalog::SEFLG_SPEED
        );

        return rad2deg(asin(0.004650467260962157 / $result['xx'][2]));
    }
}