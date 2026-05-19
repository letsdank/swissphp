<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\Phenomena;
use SwissEph\PhenomenaResult;
use SwissEph\SwissDate;

final class PhenomenaResultTest extends TestCase
{
    public function testCreatesResultFromArray(): void
    {
        $result = PhenomenaResult::fromArray([
            'rc' => Catalog::SEFLG_SPEED,
            'attr' => [1.0, 0.5, 20.0, 0.1, -1.2, 0.0],
            'error' => '',
        ]);

        self::assertSame(1.0, $result->phaseAngle());
        self::assertSame(0.5, $result->illuminatedFraction());
        self::assertSame(20.0, $result->elongation());
        self::assertSame(0.1, $result->apparentDiameter());
        self::assertSame(-1.2, $result->apparentMagnitude());
        self::assertSame(0.0, $result->horizontalParallax());
        self::assertTrue($result->isOk());
    }

    public function testConvertsResultToArray(): void
    {
        $array = [
            'rc' => Catalog::SEFLG_SPEED,
            'attr' => [1.0, 0.5, 20.0, 0.1, -1.2, 0.0],
            'error' => '',
        ];

        self::assertSame($array, PhenomenaResult::fromArray($array)->toArray());
    }

    public function testIsOkDetectsErrorReturnCode(): void
    {
        $result = new PhenomenaResult(SwissDate::ERR, array_fill(0, 20, 0.0), 'error');

        self::assertFalse($result->isOk());
    }

    public function testPhenoResultWrapsArrayResult(): void
    {
        $array = Phenomena::pheno(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $result = Phenomena::phenoResult(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame($array, $result->toArray());
    }

    public function testHasError(): void
    {
        self::assertFalse(
            (new PhenomenaResult(Catalog::SEFLG_SPEED, array_fill(0, 20, 0.0), ''))->hasError()
        );

        self::assertTrue(
            (new PhenomenaResult(SwissDate::ERR, array_fill(0, 20, 0.0), 'error'))->hasError()
        );

        self::assertTrue(
            (new PhenomenaResult(Catalog::SEFLG_SPEED, array_fill(0, 20, 0.0), 'warning'))->hasError()
        );
    }

    public function testFormattedAngleAccessors(): void
    {
        $result = new PhenomenaResult(
            Catalog::SEFLG_SPEED,
            [122.61182785431, 0.23052765740636, 57.246504596218, 0.4946359835888, -8.567272191777, 0.90790736836129],
        );

        self::assertSame('122°36\'43"', $result->phaseAngleDms());
        self::assertSame('57°14\'47"', $result->elongationDms());
        self::assertSame('0°29\'41"', $result->apparentDiameterDms());
        self::assertSame('0°54\'28"', $result->horizontalParallaxDms());
    }
}