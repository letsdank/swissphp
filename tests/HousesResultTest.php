<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\HousesResult;

final class HousesResultTest extends TestCase
{
    public function testCreatesResultFromArray(): void
    {
        $result = HousesResult::fromArray([
            'cusps' => [1 => 10.0, 2 => 40.0],
            'ascmc' => [0 => 10.0, 1 => 280.0, 2 => 300.0, 3 => 120.0, 4 => 11.0, 5 => 12.0, 6 => 13.0, 7 => 14.0],
        ]);

        self::assertSame(10.0, $result->cusp(1));
        self::assertSame(10.0, $result->ascendant());
        self::assertSame(280.0, $result->mc());
        self::assertSame(300.0, $result->armc());
        self::assertSame(120.0, $result->vertex());
    }

    public function testConvertsResultToArray(): void
    {
        $array = [
            'cusps' => [1 => 10.0],
            'ascmc' => [0 => 10.0, 1 => 280.0, 2 => 300.0],
        ];

        self::assertSame($array, HousesResult::fromArray($array)->toArray());
    }

    public function testCuspRejectsMissingIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HousesResult::fromArray([
            'cusps' => [1 => 10.0],
            'ascmc' => [0 => 10.0, 1 => 280.0, 2 => 300.0],
        ])->cusp(13);
    }

    public function testReturnsCuspsAndAscmcArrays(): void
    {
        $cusps = [1 => 10.0, 2 => 40.0];
        $ascmc = [0 => 10.0, 1 => 280.0, 2 => 300.0];

        $result = new HousesResult($cusps, $ascmc);

        self::assertSame($cusps, $result->cusps());
        self::assertSame($ascmc, $result->ascmc());
    }

    public function testHasCusp(): void
    {
        $result = new HousesResult([1 => 10.0], [0 => 10.0]);

        self::assertTrue($result->hasCusp(1));
        self::assertFalse($result->hasCusp(2));
    }

    public function testAxisReturnsAscmcValue(): void
    {
        $result = new HousesResult([1 => 10.0], [0 => 10.0, 1 => 280.0]);

        self::assertSame(10.0, $result->axis(0));
        self::assertSame(280.0, $result->axis(1));
    }

    public function testAxisRejectsMissingIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new HousesResult([1 => 10.0], [0 => 10.0]))->axis(9);
    }

    public function testFormattedCuspAccessors(): void
    {
        $result = new HousesResult(
            [1 => 86.811765333333, 2 => 99.717949055556],
            [0 => 86.811765333333, 1 => 308.040521666667, 2 => 310.457072444444, 3 => 214.699781472222]
        );

        self::assertSame('86°48\'42"', $result->cuspDms(1));
        self::assertSame('26 ge 48\'42"', $result->cuspZodiac(1));
    }

    public function testFormattedAxisAccessors(): void
    {
        $result = new HousesResult(
            [1 => 86.811765333333, 2 => 99.717949055556],
            [0 => 86.811765333333, 1 => 308.040521666667, 2 => 310.457072444444, 3 => 214.699781472222]
        );

        self::assertSame('26 ge 48\'42"', $result->ascendantZodiac());
        self::assertSame('8 aq 02\'26"', $result->mcZodiac());
        self::assertSame('20h41m50s', $result->armcHms());
        self::assertSame('4 sc 41\'59"', $result->vertexZodiac());
    }

    public function testCuspSignAccessors(): void
    {
        $result = new HousesResult(
            [1 => 86.811765333333],
            [0 => 86.811765333333]
        );

        self::assertSame(2, $result->cuspSign(1));
        self::assertSame('ge', $result->cuspSignShortName(1));
        self::assertSame('Gemini', $result->cuspSignName(1));
        self::assertEqualsWithDelta(26.811765333333, $result->cuspDegreeInSign(1), 1e-12);
    }
}