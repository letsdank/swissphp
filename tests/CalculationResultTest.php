<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Aspect;
use SwissEph\AspectSet;
use SwissEph\CalculationResult;
use SwissEph\Catalog;
use SwissEph\SwissDate;

final class CalculationResultTest extends TestCase
{
    public function testCreatesResultFromArray(): void
    {
        $result = CalculationResult::fromArray([
            'rc' => Catalog::SEFLG_SPEED,
            'xx' => [1.0, 2.0, 3.0, 4.0, 5.0, 6.0],
            'error' => '',
        ]);

        self::assertSame(Catalog::SEFLG_SPEED, $result->rc);
        self::assertSame([1.0, 2.0, 3.0, 4.0, 5.0, 6.0], $result->xx);
        self::assertSame('', $result->error);
    }

    public function testConvertsResultToArray(): void
    {
        $result = new CalculationResult(
            Catalog::SEFLG_SPEED,
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0],
            ''
        );

        self::assertSame([
            'rc' => Catalog::SEFLG_SPEED,
            'xx' => [1.0, 2.0, 3.0, 4.0, 5.0, 6.0],
            'error' => '',
        ], $result->toArray());
    }

    public function testNamedAccessors(): void
    {
        $result = new CalculationResult(
            Catalog::SEFLG_SPEED,
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0],
            ''
        );

        self::assertSame(1.0, $result->longitude());
        self::assertSame(2.0, $result->latitude());
        self::assertSame(3.0, $result->distance());
        self::assertSame(4.0, $result->longitudeSpeed());
        self::assertSame(5.0, $result->latitudeSpeed());
        self::assertSame(6.0, $result->distanceSpeed());
    }

    public function testMissingSpeedAccessorsDefaultToZero(): void
    {
        $result = new CalculationResult(Catalog::SEFLG_SWIEPH, [1.0, 2.0, 3.0]);

        self::assertSame(0.0, $result->longitudeSpeed());
        self::assertSame(0.0, $result->latitudeSpeed());
        self::assertSame(0.0, $result->distanceSpeed());
    }

    public function testIsOk(): void
    {
        self::assertTrue((new CalculationResult(Catalog::SEFLG_SWIEPH, [1.0, 2.0, 3.0]))->isOk());
        self::assertFalse((new CalculationResult(SwissDate::ERR, [0.0, 0.0, 0.0], 'error'))->isOk());
    }

    public function testHasError(): void
    {
        self::assertFalse((new CalculationResult(Catalog::SEFLG_SWIEPH, [1.0, 2.0, 3.0]))->hasError());
        self::assertTrue((new CalculationResult(SwissDate::ERR, [0.0, 0.0, 0.0], 'error'))->hasError());
    }

    public function testFlagAccessors(): void
    {
        $result = new CalculationResult(
            Catalog::SEFLG_SPEED
            | Catalog::SEFLG_SIDEREAL
            | Catalog::SEFLG_EQUATORIAL
            | Catalog::SEFLG_XYZ
            | Catalog::SEFLG_RADIANS
            | Catalog::SEFLG_TOPOCTR,
            [1.0, 2.0, 3.0, 4.0, 5.0, 6.0]
        );

        self::assertTrue($result->hasFlag(Catalog::SEFLG_SPEED));
        self::assertTrue($result->hasSpeed());
        self::assertTrue($result->isSidereal());
        self::assertTrue($result->isEquatorial());
        self::assertTrue($result->isCartesian());
        self::assertTrue($result->isRadians());
        self::assertTrue($result->isTopocentric());
        self::assertFalse($result->hasFlag(Catalog::SEFLG_HELCTR));
    }

    public function testSpeed3CountsAsSpeed(): void
    {
        $result = new CalculationResult(Catalog::SEFLG_SPEED3, [1.0, 2.0, 3.0, 4.0, 5.0, 6.0]);

        self::assertTrue($result->hasSpeed());
    }

    public function testFormattedPositionAccessors(): void
    {
        $result = new CalculationResult(
            Catalog::SEFLG_SPEED,
            [280.3689187, -23.0324303, 0.9833, 1.0194342, 0.0793019, 0.0]
        );

        self::assertSame('280°22\'08"', $result->longitudeDms());
        self::assertSame('-23°01\'57"', $result->latitudeDms());
        self::assertSame('10 cp 22\'08"', $result->longitudeZodiac());
        self::assertSame('18h41m29s', $result->rightAscensionHms());
        self::assertSame('1°01\'10"', $result->longitudeSpeedDms());
        self::assertSame('0°04\'45"', $result->latitudeSpeedDms());
    }

    public function testZodiacSignAccessors(): void
    {
        $result = new CalculationResult(
            Catalog::SEFLG_SWIEPH,
            [280.3689187, 0.0, 1.0]
        );

        self::assertSame(9, $result->zodiacSign());
        self::assertSame('cp', $result->zodiacSignShortName());
        self::assertSame('Capricorn', $result->zodiacSignName());
        self::assertEqualsWithDelta(10.3689187, $result->degreeInSign(), 1e-12);
    }

    public function testAspectHelpers(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SWIEPH, [10.0, 0.0, 1.0]);
        $second = new CalculationResult(Catalog::SEFLG_SWIEPH, [101.5, 0.0, 1.0]);

        self::assertEqualsWithDelta(91.5, $first->angularDistanceTo($second), 1e-12);

        $aspect = $first->nearestMajorAspectTo($second);

        self::assertSame(90.0, $aspect->angle);
        self::assertSame('square', $aspect->name);
        self::assertEqualsWithDelta(1.5, $aspect->orb, 1e-12);

        self::assertTrue($first->isAspectTo($second, 90.0, 2.0));
        self::assertFalse($first->isAspectTo($second, 90.0, 1.0));
    }

    public function testMajorAspectToReturnsNullableAspect(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SWIEPH, [10.0, 0.0, 1.0]);
        $second = new CalculationResult(Catalog::SEFLG_SWIEPH, [101.5, 0.0, 1.0]);

        self::assertNotNull($first->majorAspectTo($second, 2.0));
        self::assertNull($first->majorAspectTo($second, 1.0));
    }

    public function testApplyingAndSeparatingAspectHelpers(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]);
        $second = new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]);

        self::assertTrue($first->isApplyingAspectTo($second, Aspect::SQUARE));
        self::assertFalse($first->isSeparatingAspectTo($second, Aspect::SQUARE));
    }

    public function testMajorAspectResultToReturnsAspectWithApplyingState(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]);
        $second = new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]);

        $result = $first->majorAspectResultTo($second, 2.0);

        self::assertNotNull($result);
        self::assertSame('square', $result->name());
        self::assertTrue($result->isApplying());
    }

    public function testMajorAspectResultToReturnsNullOutsideOrb(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]);
        $second = new CalculationResult(Catalog::SEFLG_SPEED, [101.5, 0.0, 1.0, 0.0, 0.0, 0.0]);

        self::assertNull($first->majorAspectResultTo($second, 1.0));
    }

    public function testMajorOrMinorAspectHelpers(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SWIEPH, [10.0, 0.0, 1.0]);
        $second = new CalculationResult(Catalog::SEFLG_SWIEPH, [56.0, 0.0, 1.0]);

        $nearest = $first->nearestMajorOrMinorAspectTo($second);

        self::assertSame(45.0, $nearest->angle);
        self::assertSame('semisquare', $nearest->name);
        self::assertNotNull($first->majorOrMinorAspectTo($second, 1.5));
        self::assertNull($first->majorOrMinorAspectTo($second, 0.5));
    }

    public function testAspectSetHelpers(): void
    {
        $first = new CalculationResult(Catalog::SEFLG_SPEED, [10.0, 0.0, 1.0, 1.0, 0.0, 0.0]);
        $second = new CalculationResult(Catalog::SEFLG_SPEED, [56.0, 0.0, 1.0, 0.0, 0.0, 0.0]);
        $set = AspectSet::majorAndMinor(2.0);

        $aspect = $first->aspectFromSetTo($second, $set);
        $result = $first->aspectResultFromSetTo($second, $set);

        self::assertNotNull($aspect);
        self::assertSame('semisquare', $aspect->name);

        self::assertNotNull($result);
        self::assertSame('semisquare', $result->name());
    }
}