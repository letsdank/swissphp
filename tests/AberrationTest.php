<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Aberration;
use SwissEph\Catalog;
use SwissEph\Coordinates;
use SwissEph\EarthPosition;
use SwissEph\PlanetPosition;

final class AberrationTest extends TestCase
{
    public function testAnnualAberrationForMercuryRectangularPosition(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_MERCURY, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $aberrated = Aberration::annual($position, $earth);

        self::assertEqualsWithDelta(0.046753920215590, $aberrated[0], 1e-12);
        self::assertEqualsWithDelta(-1.414483597184783, $aberrated[1], 1e-12);
        self::assertEqualsWithDelta(-0.024575552008791, $aberrated[2], 1e-12);
        self::assertEqualsWithDelta(0.038570213433896, $aberrated[3], 1e-12);
        self::assertEqualsWithDelta(-0.003302679031412, $aberrated[4], 1e-12);
        self::assertEqualsWithDelta(-0.002488576407954, $aberrated[5], 1e-12);
    }

    public function testAnnualAberrationForMercuryPolarPosition(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_MERCURY, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $polar = self::fromCartesian(Aberration::annual($position, $earth));

        self::assertEqualsWithDelta(271.893148408144725, $polar[0], 1e-12);
        self::assertEqualsWithDelta(-0.994826255027362, $polar[1], 1e-12);
        self::assertEqualsWithDelta(1.415469439273369, $polar[2], 1e-12);
        self::assertEqualsWithDelta(1.556222172304121, $polar[3], 1e-10);
        self::assertEqualsWithDelta(-0.097502820742271, $polar[4], 1e-12);
        self::assertEqualsWithDelta(0.004617586191463, $polar[5], 1e-12);
    }

    public function testAnnualAberrationWithoutSpeedLeavesSpeedUncorrected(): void
    {
        $tjdEt = 2451545.000738760;

        $position = self::toCartesian(PlanetPosition::geocentricLightTime(Catalog::SE_VENUS, $tjdEt));
        $earth = self::toCartesian(EarthPosition::heliocentric($tjdEt));

        $aberrated = Aberration::annual($position, $earth, false);

        self::assertEqualsWithDelta(-0.541237797099768, $aberrated[0], 1e-12);
        self::assertEqualsWithDelta(-0.999732991755694, $aberrated[1], 1e-12);
        self::assertEqualsWithDelta(0.041017335101213, $aberrated[2], 1e-12);

        self::assertSame($position[3], $aberrated[3]);
        self::assertSame($position[4], $aberrated[4]);
        self::assertSame($position[5], $aberrated[5]);
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function toCartesian(array $position): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = deg2rad($position[3]);
        $position[4] = deg2rad($position[4]);

        return Coordinates::polcartSp($position);
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function fromCartesian(array $position): array
    {
        $polar = Coordinates::cartpolSp($position);

        return [
            rad2deg($polar[0]),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];
    }
}