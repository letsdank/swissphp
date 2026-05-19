<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\Phenomena;
use SwissEph\SwissDate;

final class PhenomenaTest extends TestCase
{
    public function testSunPhenomenaMatchesInternalMoshierFixture(): void
    {
        $result = Phenomena::pheno(2451545.0, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame('', $result['error']);
        self::assertCount(20, $result['attr']);
        self::assertEqualsWithDelta(0.0, $result['attr'][0], 1e-12);
        self::assertEqualsWithDelta(0.0, $result['attr'][1], 1e-12);
        self::assertEqualsWithDelta(0.0, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(0.5421754201598, $result['attr'][3], 1e-12);
        self::assertEqualsWithDelta(-26.89650901146, $result['attr'][4], 1e-11);
    }

    public function testMoonPhenomenaGeometryMatchesInternalMoshierFixture(): void
    {
        $result = Phenomena::pheno(2451545.0, Catalog::SE_MOON, Catalog::SEFLG_SPEED);

        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(122.61182785431, $result['attr'][0], 1e-11);
        self::assertEqualsWithDelta(0.23052765740636, $result['attr'][1], 1e-14);
        self::assertEqualsWithDelta(57.246504596218, $result['attr'][2], 1e-11);
        self::assertEqualsWithDelta(0.4946359835888, $result['attr'][3], 1e-13);
        self::assertEqualsWithDelta(-8.567272191777, $result['attr'][4], 1e-12);
        self::assertEqualsWithDelta(0.90790736836129, $result['attr'][5], 1e-13);
    }

    public function testPlanetPhenomenaGeometryMatchesInternalMoshierFixture(): void
    {
        $result = Phenomena::pheno(2451545.0, Catalog::SE_MERCURY, Catalog::SEFLG_SPEED);

        self::assertSame('', $result['error']);
        self::assertEqualsWithDelta(18.22833598398, $result['attr'][0], 1e-11);
        self::assertEqualsWithDelta(0.97490873409014, $result['attr'][1], 1e-14);
        self::assertEqualsWithDelta(8.5377896309281, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(0.0013201120185848, $result['attr'][3], 1e-16);
        self::assertEqualsWithDelta(-0.734469596796, $result['attr'][4], 1e-12);
    }

    public function testPhenoUtConvertsUtToEt(): void
    {
        $ut = 2451545.0;
        $expected = Phenomena::pheno($ut + DeltaT::deltatEx($ut, Catalog::SEFLG_SPEED), Catalog::SE_SUN, Catalog::SEFLG_SPEED);
        $actual = Phenomena::phenoUt($ut, Catalog::SE_SUN, Catalog::SEFLG_SPEED);

        self::assertSame($expected, $actual);
    }

    public function testPlanetMagnitudesUseMallamaStyleFormulas(): void
    {
        self::assertEqualsWithDelta(-4.066345398839, Phenomena::pheno(2451545.0, Catalog::SE_VENUS, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(1.035949788937, Phenomena::pheno(2451545.0, Catalog::SE_MARS, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(-2.520522484922, Phenomena::pheno(2451545.0, Catalog::SE_JUPITER, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(0.102372390093, Phenomena::pheno(2451545.0, Catalog::SE_SATURN, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(5.930447458772, Phenomena::pheno(2451545.0, Catalog::SE_URANUS, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(7.852844590704, Phenomena::pheno(2451545.0, Catalog::SE_NEPTUNE, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
        self::assertEqualsWithDelta(13.863018379224, Phenomena::pheno(2451545.0, Catalog::SE_PLUTO, Catalog::SEFLG_SPEED)['attr'][4], 1e-12);
    }

    public function testPhenoMapsPlutoAsteroidNumberToPluto(): void
    {
        $expected = Phenomena::pheno(
            2451545.0,
            Catalog::SE_PLUTO,
            Catalog::SEFLG_SPEED
        );

        $actual = Phenomena::pheno(
            2451545.0,
            Catalog::SE_AST_OFFSET + 134340,
            Catalog::SEFLG_SPEED
        );

        self::assertSame($expected, $actual);
    }

    public function testPhenoReturnsErrorForUnsupportedBody(): void
    {
        $result = Phenomena::pheno(
            2451545.0,
            Catalog::SE_CHIRON,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertSame('Unsupported planet or flag combination.', $result['error']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
    }

    public function testPhenoReturnsErrorOutsideMoshierRange(): void
    {
        $result = Phenomena::pheno(
            2818001.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('outside Moshier planet range', $result['error']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
    }

    public function testPhenoUtReturnsErrorOutsideMoshierRange(): void
    {
        $result = Phenomena::phenoUt(
            2818001.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED
        );

        self::assertSame(SwissDate::ERR, $result['rc']);
        self::assertStringContainsString('outside Moshier planet range', $result['error']);
        self::assertSame(array_fill(0, 20, 0.0), $result['attr']);
    }

    public function testPhenoAcceptsHeliocentricFlag(): void
    {
        $result = Phenomena::pheno(
            2451545.0,
            Catalog::SE_MERCURY,
            Catalog::SEFLG_SPEED | Catalog::SEFLG_HELCTR
        );

        self::assertSame('', $result['error']);
        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result['rc']);
        self::assertEqualsWithDelta(18.22833598398, $result['attr'][0], 1e-11);
        self::assertEqualsWithDelta(0.97490873409014, $result['attr'][1], 1e-14);
        self::assertEqualsWithDelta(8.5377896309281, $result['attr'][2], 1e-12);
        self::assertEqualsWithDelta(0.0013201120185848, $result['attr'][3], 1e-16);
        self::assertEqualsWithDelta(-0.734469596796, $result['attr'][4], 1e-12);
    }
}