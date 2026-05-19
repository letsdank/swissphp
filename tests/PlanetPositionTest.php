<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\DeltaT;
use SwissEph\PlanetPosition;

final class PlanetPositionTest extends TestCase
{
    public function testMercuryGeocentricPositionFromMoshier(): void
    {
        $position = PlanetPosition::geocentric(Catalog::SE_MERCURY, 2451545.000738760);

        self::assertEqualsWithDelta(271.905871364797406, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.995623256316812, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415528303685689, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556298504995407, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097475371004062, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004611752572531, $position[5], 1e-12);
    }

    public function testVenusHeliocentricPositionFromMoshier(): void
    {
        $position = PlanetPosition::heliocentric(Catalog::SE_VENUS, 2451545.000738760);

        self::assertEqualsWithDelta(182.604114272313637, $position[0], 1e-12);
        self::assertEqualsWithDelta(3.264578508684154, $position[1], 1e-12);
        self::assertEqualsWithDelta(0.720212900185006, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.618409302352575, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.026253794987774, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.000105768516501, $position[5], 1e-12);
    }

    public function testSupportedPlanets(): void
    {
        self::assertTrue(PlanetPosition::isSupported(Catalog::SE_MERCURY));
        self::assertTrue(PlanetPosition::isSupported(Catalog::SE_PLUTO));
        self::assertFalse(PlanetPosition::isSupported(Catalog::SE_SUN));
        self::assertFalse(PlanetPosition::isSupported(Catalog::SE_MOON));
    }

    public function testUnsupportedPlanetThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PlanetPosition::geocentric(Catalog::SE_MOON, 2451545.0);
    }

    public function testMercuryGeocentricLightTimePositionFromMoshier(): void
    {
        $position = PlanetPosition::geocentricLightTime(Catalog::SE_MERCURY, 2451545.000738760);

        self::assertEqualsWithDelta(271.898874949681272, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.994841160183166, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556245605965642, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097501577762105, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004617586192695, $position[5], 1e-12);
    }

    public function testLightTimeChangesOnlyGeocentricPosition(): void
    {
        $geometric = PlanetPosition::geocentric(Catalog::SE_VENUS, 2451545.000738760);
        $lightTime = PlanetPosition::geocentricLightTime(Catalog::SE_VENUS, 2451545.000738760);

        self::assertGreaterThan(1e-4, abs($geometric[0] - $lightTime[0]));
        self::assertGreaterThan(1e-5, abs($geometric[2] - $lightTime[2]));

        $heliocentric = PlanetPosition::heliocentric(Catalog::SE_VENUS, 2451545.000738760);

        self::assertEqualsWithDelta(182.604114272313637, $heliocentric[0], 1e-12);
    }

    public function testGeocentricThrowsOutsideMoshierPlanetRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('jd 2818001.000000 outside Moshier planet range 625000.50 .. 2818000.50');

        PlanetPosition::geocentric(Catalog::SE_MERCURY, 2818001.0);
    }

    public function testMercuryApparentPositionFromMoshier(): void
    {
        $position = PlanetPosition::apparent(Catalog::SE_MERCURY, 2451545.000738760);

        self::assertEqualsWithDelta(271.889246009586032, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556224363540365, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097502946397082, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004617585896809, $position[5], 1e-12);
    }

    public function testMercuryApparentCanSkipNutation(): void
    {
        $position = PlanetPosition::apparent(Catalog::SE_MERCURY, 2451545.000738760, false);

        self::assertEqualsWithDelta(271.893143606859724, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.994826814207524, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556221870023774, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097502946397082, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004617585896809, $position[5], 1e-12);
    }

    public function testMercuryApparentCanSkipAberration(): void
    {
        $position = PlanetPosition::apparent(
            Catalog::SE_MERCURY,
            2451545.000738760,
            true,
            true,
            false
        );

        self::assertEqualsWithDelta(271.894972551051751, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.994841719380015, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273374, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556247797204471, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097501703415915, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004617585899393, $position[5], 1e-12);
    }

    public function testMercuryApparentCanSkipDeflection(): void
    {
        $position = PlanetPosition::apparent(
            Catalog::SE_MERCURY,
            2451545.000738760,
            true,
            false,
            true
        );

        self::assertEqualsWithDelta(271.889250810871033, $position[0], 1e-12);
        self::assertEqualsWithDelta(-0.994826255027362, $position[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $position[2], 1e-12);
        self::assertEqualsWithDelta(1.556224665820713, $position[3], 1e-10);
        self::assertEqualsWithDelta(-0.097502820742271, $position[4], 1e-12);
        self::assertEqualsWithDelta(0.004617586191463, $position[5], 1e-12);
    }

    public function testVenusAndMarsApparentPositionsFromMoshier(): void
    {
        $venus = PlanetPosition::apparent(Catalog::SE_VENUS, 2451545.000738760);
        $mars = PlanetPosition::apparent(Catalog::SE_MARS, 2451545.000738760);

        self::assertEqualsWithDelta(241.565769042355470, $venus[0], 1e-12);
        self::assertEqualsWithDelta(2.066343517808433, $venus[1], 1e-12);
        self::assertEqualsWithDelta(1.137579372876022, $venus[2], 1e-12);

        self::assertEqualsWithDelta(327.963283604682204, $mars[0], 1e-12);
        self::assertEqualsWithDelta(-1.067778992868767, $mars[1], 1e-12);
        self::assertEqualsWithDelta(1.849687426071225, $mars[2], 1e-12);
    }

    public function testApparentUtConvertsUtToEt(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = PlanetPosition::apparent(Catalog::SE_MERCURY, $tjdEt);
        $ut = PlanetPosition::apparentUt(Catalog::SE_MERCURY, $tjdUt);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }

    public function testApparentUtPreservedCorrectionOptions(): void
    {
        $tjdUt = 2451545.0;
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);

        $et = PlanetPosition::apparent(
            Catalog::SE_MERCURY,
            $tjdEt,
            false,
            true,
            false
        );

        $ut = PlanetPosition::apparentUt(
            Catalog::SE_MERCURY,
            $tjdUt,
            false,
            true,
            false
        );

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($et[$i], $ut[$i], 1e-12);
        }
    }

    public function testApparentResultWrapsApparentPosition(): void
    {
        $position = PlanetPosition::apparent(Catalog::SE_MERCURY, 2451545.000738760);
        $result = PlanetPosition::apparentResult(Catalog::SE_MERCURY, 2451545.000738760);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result->rc);
        self::assertSame('', $result->error);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($position[$i], $result->xx[$i], 1e-12);
        }
    }

    public function testApparentUtResultWrapsApparentUtPosition(): void
    {
        $position = PlanetPosition::apparentUt(Catalog::SE_MERCURY, 2451545.0);
        $result = PlanetPosition::apparentUtResult(Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SEFLG_SPEED | Catalog::SEFLG_SWIEPH, $result->rc);

        for ($i = 0; $i <= 5; $i++) {
            self::assertEqualsWithDelta($position[$i], $result->xx[$i], 1e-12);
        }
    }
}