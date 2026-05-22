<?php

declare(strict_types=1);

namespace SwissEph;

use RuntimeException;

final class NatalChartCalculator
{
    public const DEFAULT_BODIES = [
        Catalog::SE_SUN,
        Catalog::SE_MOON,
        Catalog::SE_MERCURY,
        Catalog::SE_VENUS,
        Catalog::SE_MARS,
        Catalog::SE_JUPITER,
        Catalog::SE_SATURN,
        Catalog::SE_URANUS,
        Catalog::SE_NEPTUNE,
        Catalog::SE_PLUTO,
    ];

    /**
     * @param array<int, int> $bodies
     */
    public static function calculate(
        float      $tjdUt,
        float      $geoLat,
        float      $geoLon,
        string|int $houseSystem = Houses::HSYS_PLACIDUS,
        array      $bodies = self::DEFAULT_BODIES,
        int        $flags = Catalog::SEFLG_DEFAULTEPH,
        ?AspectSet $aspectSet = null,
    ): NatalChart
    {
        $flags = Catalog::normalizeEphemerisFlags($flags) | Catalog::SEFLG_SPEED;

        $houseArray = Houses::houses($tjdUt, $geoLat, $geoLon, $houseSystem);
        $houses = self::buildHouses($houseArray['cusps']);

        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $eps = SiderealTime::meanObliquity($tjdEt) + SiderealTime::nutationApprox($tjdEt)['deps'];

        $points = [];

        foreach ($bodies as $body) {
            $calc = Calculator::calcUt($tjdUt, $body, $flags);

            if ($calc['rc'] === SwissDate::ERR) {
                throw new RuntimeException($calc['error']);
            }

            $name = Catalog::planetName($body);
            $house = (int)floor(Houses::housePosition(
                $houseArray['ascmc'][2],
                $geoLat,
                $eps,
                $houseSystem,
                [$calc['xx'][0], $calc['xx'][1]]
            ));

            if ($house < 1) {
                $house = 1;
            } elseif ($house > 12) {
                $house = (($house - 1) % 12) + 1;
            }

            $points[$name] = new NatalChartPoint(
                $body,
                $name,
                $calc['xx'][0],
                $calc['xx'][1],
                $calc['xx'][2],
                $calc['xx'][3] ?? 0.0,
                $house,
            );
        }

        return new NatalChart(
            $tjdUt,
            $geoLat,
            $geoLon,
            is_int($houses) ? chr($houses & 0xff) : $houseSystem,
            $points,
            $houses,
            $aspectSet === null ? [] : self::buildAspects($points, $aspectSet)
        );
    }

    /**
     * @param array<int, float> $cusps
     * @return array<int, NatalChartHouse>
     */
    private static function buildHouses(array $cusps): array
    {
        $houses = [];

        for ($i = 1; $i <= 12; $i++) {
            if (isset($cusps[$i])) {
                $houses[$i] = new NatalChartHouse($i, $cusps[$i]);
            }
        }

        return $houses;
    }

    /**
     * @param array<string, NatalChartPoint> $points
     * @return array<int, NatalChartAspect>
     */
    private static function buildAspects(array $points, AspectSet $aspectSet): array
    {
        $aspects = [];
        $names = array_keys($points);
        $count = count($names);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $first = $points[$names[$i]];
                $second = $points[$names[$j]];
                $aspect = $aspectSet->match($first->longitude, $second->longitude);

                if ($aspect === null) {
                    continue;
                }

                $aspects[] = new NatalChartAspect(
                    $first->name,
                    $second->name,
                    $aspect->name,
                    $aspect->angle,
                    $aspect->orb,
                    Aspect::isApplying(
                        $first->longitude,
                        $first->speedLongitude,
                        $second->longitude,
                        $second->speedLongitude,
                        $aspect->angle
                    )
                );
            }
        }

        return $aspects;
    }
}