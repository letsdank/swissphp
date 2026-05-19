<?php

declare(strict_types=1);

namespace SwissEph;

final class SiderealTime
{
    private const J2000 = 2451545.0;

    /**
     * Compatible with swe_sidtime0().
     *
     * Returns apparent sidereal time at Greenwich in hours.
     *
     * This uses the IERS 2010 polynomial part. The tiny non-polynomial
     * correction from Swiss Ephemeris is deferred until we port the full
     * nutation/procession tables.
     */
    public static function sidtime0(float $tjdUt, float $eps, float $nut): float
    {
        $jd0 = floor($tjdUt);
        $secs = $tjdUt - $jd0;

        if ($secs < 0.5) {
            $jd0 -= 0.5;
            $secs += 0.5;
        } else {
            $jd0 += 0.5;
            $secs -= 0.5;
        }

        $secs *= 86400.0;

        $jdrel = $tjdUt - self::J2000;
        $tt = ($tjdUt + DeltaT::deltatEx($tjdUt, -1) - self::J2000) / 36525.0;

        $gmst = Angle::degnorm((0.7790572732640 + 1.00273781191135448 * $jdrel) * 360.0);

        $gmst += (
                0.014506
                + $tt * (
                    4612.156534
                    + $tt * (
                        1.3915817
                        + $tt * (
                            -0.00000044
                            + $tt * (
                                -0.000029956
                                + $tt * -0.0000000368
                            )
                        )
                    )
                )
            ) / 3600.0;

        $gmst = Angle::degnorm($gmst);
        $gmst = $gmst / 15.0 * 3600.0;

        $eqeq = 240.0 * $nut * cos(deg2rad($eps));
        $gmst += $eqeq;

        $gmst -= 86400.0 * floor($gmst / 86400.0);

        return $gmst / 3600.0;
    }

    /**
     * Compatible with swe_sidtime().
     *
     * Uses compact mean-obliquity and short nutation approximations until
     * full Swiss Ephemeris nutation tables are ported.
     */
    public static function sidtime(float $tjdUt): float
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, -1);
        $nut = self::nutationApprox($tjdEt);
        $eps = self::meanObliquity($tjdEt) + $nut['deps'];

        return self::sidtime0($tjdUt, $eps, $nut['dpsi']);
    }

    /**
     * Mean obliquity of the ecliptic in degrees, IAU 2006 polynomial.
     */
    public static function meanObliquity(float $tjdEt): float
    {
        $t = ($tjdEt - self::J2000) / 36525.0;

        $seconds = 84381.406
            + $t * (
                -46.836769
                + $t * (
                    -0.0001831
                    + $t * (
                        0.00200340
                        + $t * (
                            -0.000000576
                            + $t * -0.0000000434
                        )
                    )
                )
            );

        return $seconds / 3600.0;
    }

    /**
     * Convert nutation approximation.
     *
     * @return array{dpsi:float, deps:float} degrees
     */
    public static function nutationApprox(float $tjdEt): array
    {
        $t = ($tjdEt - self::J2000) / 36525.0;

        $omega = deg2rad(Angle::degnorm(125.04452 - 1934.136261 * $t));
        $meanSun = deg2rad(Angle::degnorm(280.4665 + 36000.7698 * $t));
        $meanMoon = deg2rad(Angle::degnorm(218.3165 + 481267.8813 * $t));

        $dpsi = (
                -17.20 * sin($omega)
                - 1.32 * sin(2.0 * $meanSun)
                - 0.23 * sin(2.0 * $meanMoon)
                + 0.21 * sin(2.0 * $omega)
            ) / 3600.0;

        $deps = (
                9.20 * cos($omega)
                + 0.57 * cos(2.0 * $meanSun)
                + 0.10 * cos(2.0 * $meanMoon)
                - 0.09 * cos(2.0 * $omega)
            ) / 3600.0;

        return [
            'dpsi' => $dpsi,
            'deps' => $deps,
        ];
    }
}