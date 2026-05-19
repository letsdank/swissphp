<?php

declare(strict_types=1);

namespace SwissEph;

final class AzimuthAltitude
{
    /**
     * Compatible subset of swe_azalt().
     *
     * Input coordinates are polar degrees:
     * - SE_EQU2HOR: [rightAscension, declination, distance]
     * - SE_ECL2HOR: [longitude, latitude, distance]
     *
     * Output:
     * [azimuth, trueAltitude, apparentAltitude]
     *
     * Swiss azimuth convention: from south, clockwise via west.
     *
     * @param array{0:float, 1:float, 2?:float} $position
     * @return array{0:float, 1:float, 2:float}
     */
    public static function azalt(
        float    $tjdUt,
        int      $calcFlag,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        array    $position
    ): array
    {
        $armc = Angle::degnorm(SiderealTime::sidtime($tjdUt) * 15.0 + $observer->longitude);

        $equatorial = [
            $position[0],
            $position[1],
            $position[2] ?? 1.0,
        ];

        if ($calcFlag === Catalog::SE_ECL2HOR) {
            $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
            $nutation = SiderealTime::nutationApprox($tjdEt);
            $trueObliquity = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];

            $equatorial = Coordinates::cotrans($equatorial, -$trueObliquity);
        }

        $meridianDistance = Angle::degnorm($equatorial[0] - $armc);

        $horizontal = [
            Angle::degnorm($meridianDistance - 90.0),
            $equatorial[1],
            1.0,
        ];

        $horizontal = Coordinates::cotrans($horizontal, 90.0 - $observer->latitude);

        $horizontal[0] = Angle::degnorm($horizontal[0] + 90.0);

        $azimuth = 360.0 - $horizontal[0];
        $trueAltitude = $horizontal[1];

        if ($pressure == 0.0) {
            $pressure = self::estimatePressure($observer->altitude);
        }

        $apparent = Refraction::extended(
            $trueAltitude,
            $observer->altitude,
            $pressure,
            $temperature,
            Refraction::DEFAULT_LAPSE_RATE,
            Catalog::SE_TRUE_TO_APP
        );

        return [
            $azimuth,
            $trueAltitude,
            $apparent['altitude'],
        ];
    }

    /**
     * Compatible subset of swe_azalt_rev().
     *
     * Input:
     * [azimuth, trueAltitude]
     *
     * Swiss azimuth convention: from south, clockwise via west.
     *
     * Output:
     * - SE_HOR2EQU: [rightAscension, declination]
     * - SE_HOR2ECL: [longitude, latitude]
     *
     * @param array{0:float, 1:float} $position
     * @return array{0:float, 1:float}
     */
    public static function azaltRev(
        float    $tjdUt,
        int      $calcFlag,
        Observer $observer,
        array    $position
    ): array
    {
        $armc = Angle::degnorm(SiderealTime::sidtime($tjdUt) * 15.0 + $observer->longitude);

        $horizontal = [
            360.0 - $position[0],
            $position[1],
            1.0,
        ];

        $horizontal[0] = Angle::degnorm($horizontal[0] - 90.0);

        $equatorial = Coordinates::cotrans(
            $horizontal,
            $observer->latitude - 90.0
        );

        $equatorial[0] = Angle::degnorm($equatorial[0] + $armc + 90.0);

        if ($calcFlag === Catalog::SE_HOR2ECL) {
            $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
            $nutation = SiderealTime::nutationApprox($tjdEt);
            $trueObliquity = SiderealTime::meanObliquity($tjdEt) + $nutation['deps'];

            $ecliptic = Coordinates::cotrans(
                [$equatorial[0], $equatorial[1], 1.0],
                $trueObliquity
            );

            return [$ecliptic[0], $ecliptic[1]];
        }

        return [$equatorial[0], $equatorial[1]];
    }

    private static function estimatePressure(float $altitude): float
    {
        return 1013.25 * (1.0 - 0.0065 * $altitude / 288.0) ** 5.255;
    }
}