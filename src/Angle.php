<?php

declare(strict_types=1);

namespace SwissEph;

final class Angle
{
    public const SPLIT_DEG_ROUND_SEC = 1;
    public const SPLIT_DEG_ROUND_MIN = 2;
    public const SPLIT_DEG_ROUND_DEG = 4;
    public const SPLIT_DEG_ZODIACAL = 8;
    public const SPLIT_DEG_KEEP_SIGN = 16;
    public const SPLIT_DEG_KEEP_DEG = 32;

    private const ZODIAC_SIGNS_SHORT = ['ar', 'ta', 'ge', 'cn', 'le', 'vi', 'li', 'sc', 'sa', 'cp', 'aq', 'pi'];

    private const ZODIAC_SIGNS_LONG = [
        'Aries',
        'Taurus',
        'Gemini',
        'Cancer',
        'Leo',
        'Virgo',
        'Libra',
        'Scorpio',
        'Sagittarius',
        'Capricorn',
        'Aquarius',
        'Pisces',
    ];

    public static function degnorm(float $x): float
    {
        $y = fmod($x, 360.0);

        if (abs($y) < 1e-13) {
            $y = 0.0;
        }

        if ($y < 0.0) {
            $y += 360.0;
        }

        return $y;
    }

    public static function radnorm(float $x): float
    {
        $twoPi = 2.0 * M_PI;
        $y = fmod($x, $twoPi);

        if (abs($y) < 1e-13) {
            $y = 0.0;
        }

        if ($y < 0.0) {
            $y += $twoPi;
        }

        return $y;
    }

    public static function difdegn(float $p1, float $p2): float
    {
        return self::degnorm($p1 - $p2);
    }

    public static function difdeg2n(float $p1, float $p2): float
    {
        $dif = self::degnorm($p1 - $p2);

        if ($dif >= 180.0) {
            return $dif - 360.0;
        }

        return $dif;
    }

    public static function difrad2n(float $p1, float $p2): float
    {
        $dif = self::radnorm($p1 - $p2);

        if ($dif >= M_PI) {
            return $dif - 2.0 * M_PI;
        }

        return $dif;
    }

    public static function degMidp(float $x1, float $x0): float
    {
        $d = self::difdeg2n($x1, $x0);

        return self::degnorm($x0 + $d / 2.0);
    }

    public static function radMidp(float $x1, float $x0): float
    {
        return deg2rad(self::degMidp(rad2deg($x1), rad2deg($x0)));
    }

    public static function zodiacSign(float $degrees): int
    {
        return (int)(self::degnorm($degrees) / 30.0);
    }

    public static function zodiacSignShortName(float $degrees): string
    {
        return self::ZODIAC_SIGNS_SHORT[self::zodiacSign($degrees)];
    }

    public static function zodiacSignName(float $degrees): string
    {
        return self::ZODIAC_SIGNS_LONG[self::zodiacSign($degrees)];
    }

    public static function degreeInSign(float $degrees): float
    {
        return fmod(self::degnorm($degrees), 30.0);
    }

    /**
     * Compatible with swe_split_deg(), except SE_SPLIT_DEG_NAKSHATRA is not implemented yet.
     *
     * @return array{deg:int, min:int, sec:int, secfr:float, sign:int}
     */
    public static function splitDeg(float $ddeg, int $roundflag): array
    {
        $dadd = 0.0;
        $sign = 1;

        if ($ddeg < 0.0) {
            $sign = -1;
            $ddeg = -$ddeg;
        }

        if (($roundflag & self::SPLIT_DEG_ROUND_DEG) !== 0) {
            $dadd = 0.5;
        } elseif (($roundflag & self::SPLIT_DEG_ROUND_MIN) !== 0) {
            $dadd = 0.5 / 60.0;
        } elseif (($roundflag & self::SPLIT_DEG_ROUND_SEC) !== 0) {
            $dadd = 0.5 / 3600.0;
        }

        if (($roundflag & self::SPLIT_DEG_KEEP_DEG) !== 0) {
            if ((int)($ddeg + $dadd) - (int)$ddeg > 0) {
                $dadd = 0.0;
            }
        } elseif (($roundflag & self::SPLIT_DEG_KEEP_SIGN) !== 0) {
            if (fmod($ddeg, 30.0) + $dadd >= 30.0) {
                $dadd = 0.0;
            }
        }

        $ddeg += $dadd;

        if (($roundflag & self::SPLIT_DEG_ZODIACAL) !== 0) {
            $sign = (int)($ddeg / 30.0);

            if ($sign === 12) {
                $sign = 0;
            }

            $ddeg = fmod($ddeg, 30.0);
        }

        $deg = (int)$ddeg;
        $ddeg -= $deg;

        $min = (int)($ddeg * 60.0);
        $ddeg -= $min / 60.0;

        $sec = (int)($ddeg * 3600.0);

        if (($roundflag & (self::SPLIT_DEG_ROUND_DEG | self::SPLIT_DEG_ROUND_MIN | self::SPLIT_DEG_ROUND_SEC)) === 0) {
            $secfr = $ddeg * 3600.0 - $sec;
        } else {
            $secfr = (float)$sec;
        }

        return [
            'deg' => $deg,
            'min' => $min,
            'sec' => $sec,
            'secfr' => $secfr,
            'sign' => $sign,
        ];
    }

    public static function formatDms(float $degrees, int $roundflag = self::SPLIT_DEG_ROUND_SEC): string
    {
        $split = self::splitDeg($degrees, $roundflag);
        $prefix = $split['sign'] < 0 ? '-' : '';

        return sprintf(
            '%s%d°%02d\'%02d"',
            $prefix,
            $split['deg'],
            $split['min'],
            $split['sec']
        );
    }

    public static function formatZodiac(float $degrees, int $roundflag = self::SPLIT_DEG_ROUND_SEC): string
    {
        $split = self::splitDeg($degrees, $roundflag | self::SPLIT_DEG_ZODIACAL);

        return sprintf(
            '%d %s %02d\'%02d"',
            $split['deg'],
            self::ZODIAC_SIGNS_SHORT[$split['sign']],
            $split['min'],
            $split['sec']
        );
    }

    public static function formatHms(float $degrees, int $roundflag = self::SPLIT_DEG_ROUND_SEC): string
    {
        $hours = self::degnorm($degrees) / 15.0;
        $split = self::splitDeg($hours, $roundflag);

        return sprintf(
            '%02dh%02dm%02ds',
            $split['deg'],
            $split['min'],
            $split['sec']
        );
    }
}
