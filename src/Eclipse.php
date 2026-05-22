<?php

declare(strict_types=1);

namespace SwissEph;

final class Eclipse
{
    private const AUNIT_METERS = 149597870700.0;
    private const DSUN = 1392000000.0 / self::AUNIT_METERS;
    private const DMOON = 3476300.0 / self::AUNIT_METERS;
    private const DEARTH = 6378140.0 * 2.0 / self::AUNIT_METERS;
    private const RSUN = self::DSUN / 2.0;
    private const RMOON = self::DMOON / 2.0;
    private const REARTH = self::DEARTH / 2.0;

    /**
     * Geocentric subset of swe_lun_eclipse_how().
     *
     * attr[0] umbral magnitude
     * attr[1] penumbral magnitude
     * attr[7] distance of Moon from opposition in degrees
     * attr[8] umbral magnitude
     * attr[9] Saros series number, not implemented yet
     * attr[10] Saros series member number, not implemented yet
     *
     * @return array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public static function lunarHow(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $attr = array_fill(0, 20, 0.0);
        $dcore = array_fill(0, 10, 0.0);

        $calcFlags = Catalog::normalizeEphemerisFlags($flags)
            | Catalog::SEFLG_SPEED
            | Catalog::SEFLG_EQUATORIAL
            | Catalog::SEFLG_XYZ;

        $moon = Calculator::calcUt($tjdUt, Catalog::SE_MOON, $calcFlags);
        $sun = Calculator::calcUt($tjdUt, Catalog::SE_SUN, $calcFlags);

        if ($moon['rc'] === SwissDate::ERR || $sun['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'attr' => $attr,
                'dcore' => $dcore,
                'error' => $moon['error'] !== '' ? $moon['error'] : $sun['error'],
            ];
        }

        $rm = $moon['xx'];
        $rs = $sun['xx'];

        $dm = self::vectorLength($rm);
        $ds = self::vectorLength($rs);

        $sunUnit = [$rs[0] / $ds, $rs[1] / $ds, $rs[2] / $ds];
        $moonUnit = [$rm[0] / $dm, $rm[1] / $dm, $rm[2] / $dm];

        $dctr = rad2deg(acos(self::clamp(self::dot($sunUnit, $moonUnit), -1.0, 1.0)));

        for ($i = 0; $i < 3; $i++) {
            $rs[$i] -= $rm[$i];
            $rm[$i] = -$rm[$i];
        }

        $e = [
            $rm[0] - $rs[0],
            $rm[1] - $rs[1],
            $rm[2] - $rs[2],
        ];

        $dsm = self::vectorLength($e);

        for ($i = 0; $i < 3; $i++) {
            $e[$i] /= $dsm;
        }

        $f1 = (self::RSUN - self::REARTH) / $dsm;
        $cosf1 = sqrt(1.0 - $f1 * $f1);
        $f2 = (self::RSUN + self::REARTH) / $dsm;
        $cosf2 = sqrt(1.0 - $f2 * $f2);

        $s0 = -self::dot($rm, $e);
        $r0 = sqrt($dm * $dm - $s0 * $s0);

        $d0 = abs($s0 / $dsm * (self::DSUN - self::DEARTH) - self::DEARTH)
            * (1.0 + 1.0 / 50.0)
            / $cosf1;

        $D0 = ($s0 / $dsm * (self::DSUN + self::DEARTH) + self::DEARTH)
            * (1.0 + 1.0 / 50.0)
            / $cosf2;

        $d0 /= $cosf1;
        $D0 /= $cosf2;

        $d0 *= 0.99405;
        $D0 *= 0.98813;

        $dcore[0] = $r0;
        $dcore[1] = $d0;
        $dcore[2] = $D0;
        $dcore[3] = $cosf1;
        $dcore[4] = $cosf2;

        $rc = 0;

        if ($d0 / 2.0 >= $r0 + self::RMOON / $cosf1) {
            $rc = Catalog::SE_ECL_TOTAL;
            $attr[0] = ($d0 / 2.0 - $r0 + self::RMOON) / self::DMOON;
        } elseif ($d0 / 2.0 >= $r0 - self::RMOON / $cosf1) {
            $rc = Catalog::SE_ECL_PARTIAL;
            $attr[0] = ($d0 / 2.0 - $r0 + self::RMOON) / self::DMOON;
        } elseif ($D0 / 2.0 >= $r0 - self::RMOON / $cosf2) {
            $rc = Catalog::SE_ECL_PENUMBRAL;
            $attr[0] = 0.0;
        }

        $attr[8] = $attr[0];
        $attr[1] = ($D0 / 2.0 - $r0 + self::RMOON) / self::DMOON;

        if ($rc !== 0) {
            $attr[7] = 180.0 - abs($dctr);
        }

        $attr[9] = -99999999.0;
        $attr[10] = -99999999.0;

        return [
            'rc' => $rc,
            'attr' => $attr,
            'dcore' => $dcore,
            'error' => $rc === 0 ? sprintf('no lunar eclipse at tjd = %.6F', $tjdUt) : '',
        ];
    }

    public static function lunarHowResult(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): EclipseResult
    {
        return EclipseResult::fromArray(self::lunarHow($tjdUt, $flags));
    }

    /**
     * @param array<int, float> $vector
     */
    private static function vectorLength(array $vector): float
    {
        return sqrt($vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2]);
    }

    /**
     * @param array<int, float> $first
     * @param array<int, float> $second
     */
    private static function dot(array $first, array $second): float
    {
        return $first[0] * $second[0] + $first[1] * $second[1] + $first[2] * $second[2];
    }

    private static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}