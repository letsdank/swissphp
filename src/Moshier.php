<?php

declare(strict_types=1);

namespace SwissEph;

final class Moshier
{
    public const J2000 = 2451545.0;
    public const TIMESCALE = 3652500.0;
    public const PLANET_START = 625000.5;
    public const PLANET_END = 2818000.5;
    public const PLANET_SPEED_MARGIN = 0.3;
    public const ARCSEC_TO_RAD = M_PI / (180.0 * 3600.0);

    private const FULL_CIRCLE_ARCSEC = 1296000.0;
    public const LIGHTTIME_AUNIT = 499.0047838362 / 3600.0 / 24.0;


    /** @var list<float> */
    private const FREQS = [
        53810162868.8982,
        21066413643.3548,
        12959774228.3429,
        6890507749.3988,
        1092566037.7991,
        439960985.5372,
        154248119.3933,
        78655032.0744,
        52272245.1795,
    ];

    /** @var list<float> */
    private const PHASES = [
        252.25090552 * 3600.0,
        181.97980085 * 3600.0,
        100.46645683 * 3600.0,
        355.43299958 * 3600.0,
        34.35151874 * 3600.0,
        50.07744430 * 3600.0,
        314.05500511 * 3600.0,
        304.34866548 * 3600.0,
        860492.1546,
    ];

    public static function timeParameter(float $julianDay): float
    {
        return ($julianDay - self::J2000) / self::TIMESCALE;
    }

    public static function mods3600(float $x): float
    {
        return $x - self::FULL_CIRCLE_ARCSEC * floor($x / self::FULL_CIRCLE_ARCSEC);
    }

    public static function meanArgument(int $index, float $julianDay): float
    {
        if (!isset(self::FREQS[$index], self::PHASES[$index])) {
            throw new \InvalidArgumentException(sprintf('Unknown Moshier argument index %d.', $index));
        }

        $arcsec = self::FREQS[$index] * self::timeParameter($julianDay) + self::PHASES[$index];

        return self::mods3600($arcsec) * self::ARCSEC_TO_RAD;
    }

    public static function meanArgumentDegrees(int $index, float $julianDay): float
    {
        return rad2deg(self::meanArgument($index, $julianDay));
    }

    /**
     * @return list<float>
     */
    public static function meanArguments(float $julianDay): array
    {
        $arguments = [];

        for ($i = 0; $i < count(self::FREQS); $i++) {
            $arguments[] = self::meanArgument($i, $julianDay);
        }

        return $arguments;
    }

    /**
     * @return array<int, array{sin:float, cos:float}>
     */
    public static function harmonicSinCos(float $argument, int $maxHarmonic): array
    {
        if ($maxHarmonic < 0) {
            throw new \InvalidArgumentException('Maximum harmonic must be greater than or equal to zero.');
        }

        $harmonics = [];

        for ($i = 1; $i <= $maxHarmonic; $i++) {
            $harmonics[$i] = [
                'sin' => sin($i * $argument),
                'cos' => cos($i * $argument),
            ];
        }

        return $harmonics;
    }

    public static function isInPlanetRange(float $julianDay, bool $withSpeedMargin = true): bool
    {
        $margin = $withSpeedMargin ? self::PLANET_SPEED_MARGIN : 0.0;

        return $julianDay >= self::PLANET_START - $margin
            && $julianDay <= self::PLANET_END + $margin;
    }

    public static function planetRangeError(float $julianDay): string
    {
        return sprintf(
            'jd %.6f outside Moshier planet range %.2f .. %.2f',
            $julianDay,
            self::PLANET_START,
            self::PLANET_END
        );
    }

    public static function assertPlanetRange(float $julianDay): void
    {
        if (!self::isInPlanetRange($julianDay)) {
            throw new \InvalidArgumentException(self::planetRangeError($julianDay));
        }
    }
}