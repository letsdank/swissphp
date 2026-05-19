<?php

declare(strict_types=1);

namespace SwissEph;

final class Crossing
{
    private const SUN_STEP_DAYS = 2.0;
    private const SUN_MAX_DAYS = 370.0;
    private const MOON_STEP_DAYS = 0.25;
    private const MOON_MAX_DAYS = 30.0;
    private const BISECTION_ITERATIONS = 80;
    private const CROSS_PRECISION_DEGREES = 0.001 / 3600.0;

    public static function solcross(
        float $longitude,
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::bodyCross(
            Catalog::SE_SUN,
            $longitude,
            $tjdEt,
            $flags,
            self::SUN_STEP_DAYS,
            self::SUN_MAX_DAYS
        );
    }

    public static function solcrossUt(
        float $longitude,
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $crossEt = self::solcross($longitude, $tjdEt, $flags);

        if ($crossEt < $tjdEt) {
            return $tjdUt - 1.0;
        }

        return $crossEt - DeltaT::deltatEx($crossEt, $flags);
    }

    public static function mooncross(
        float $longitude,
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::bodyCross(
            Catalog::SE_MOON,
            $longitude,
            $tjdEt,
            $flags,
            self::MOON_STEP_DAYS,
            self::MOON_MAX_DAYS
        );
    }

    public static function mooncrossUt(
        float $longitude,
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $crossEt = self::mooncross($longitude, $tjdEt, $flags);

        if ($crossEt < $tjdEt) {
            return $tjdUt - 1.0;
        }

        return $crossEt - DeltaT::deltatEx($crossEt, $flags);
    }

    /**
     * Finds the next Moon crossing through the ecliptic.
     *
     * @return array{tjd:float, longitude:float, latitude:float}
     */
    public static function mooncrossNode(
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $flags = Catalog::normalizeEphemerisFlags($flags) | Catalog::SEFLG_SPEED;
        $left = $tjdEt;
        $leftLatitude = self::moonLatitude($left, $flags);
        $end = $tjdEt + self::MOON_MAX_DAYS;

        for ($right = $tjdEt + self::MOON_STEP_DAYS; $right <= $end + 1e-12; $right += self::MOON_STEP_DAYS) {
            $rightLatitude = self::moonLatitude($right, $flags);

            if (self::crossedLatitude($leftLatitude, $rightLatitude)) {
                $crossing = self::refineMoonNodeCrossing($left, $right, $flags);
                $position = Calculator::calcApparentFlags($crossing, Catalog::SE_MOON, $flags);

                if ($position['rc'] === SwissDate::ERR) {
                    return [
                        'tjd' => $tjdEt - 1.0,
                        'longitude' => 0.0,
                        'latitude' => 0.0,
                    ];
                }

                return [
                    'tjd' => $crossing,
                    'longitude' => $position['xx'][0],
                    'latitude' => $position['xx'][1],
                ];
            }

            $left = $right;
            $leftLatitude = $rightLatitude;
        }

        return [
            'tjd' => $tjdEt - 1.0,
            'longitude' => 0.0,
            'latitude' => 0.0,
        ];
    }

    /**
     * @return array{tjd:float, longitude:float, latitude:float}
     */
    public static function mooncrossNodeUt(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $crossing = self::mooncrossNode($tjdEt, $flags);

        if ($crossing['tjd'] < $tjdEt) {
            return [
                'tjd' => $tjdUt - 1.0,
                'longitude' => 0.0,
                'latitude' => 0.0,
            ];
        }

        $crossing['tjd'] -= DeltaT::deltatEx($crossing['tjd'], $flags);

        return $crossing;
    }

    /**
     * Finds heliocentric crossing of a supported planet over a longitude.
     *
     * @return array{rc:int, tjd:float, error:string}
     */
    public static function helioCross(
        int   $body,
        float $longitude,
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): array
    {
        $body = self::normalizeHeliocentricBody($body);

        if (!PlanetPosition::isSupported($body)) {
            return [
                'rc' => SwissDate::ERR,
                'tjd' => $tjdEt - 1.0,
                'error' => sprintf('Heliocentric crossing is not possible for object %d.', $body),
            ];
        }

        $target = Angle::degnorm($longitude);

        try {
            $position = PlanetPosition::heliocentric($body, $tjdEt);
        } catch (\InvalidArgumentException $exception) {
            return [
                'rc' => SwissDate::ERR,
                'tjd' => $tjdEt - 1.0,
                'error' => $exception->getMessage(),
            ];
        }

        if ($position[3] == 0.0) {
            return [
                'rc' => SwissDate::ERR,
                'tjd' => $tjdEt - 1.0,
                'error' => 'Heliocentric longitude speed is zero.',
            ];
        }

        $distance = Angle::degnorm($target - $position[0]);
        $crossing = $direction >= 0
            ? $tjdEt + $distance / $position[3]
            : $tjdEt - (360.0 - $distance) / $position[3];

        for ($i = 0; $i < 20; $i++) {
            try {
                $position = PlanetPosition::heliocentric($body, $crossing);
            } catch (\InvalidArgumentException $exception) {
                return [
                    'rc' => SwissDate::ERR,
                    'tjd' => $tjdEt - 1.0,
                    'error' => $exception->getMessage(),
                ];
            }

            $distance = Angle::difdeg2n($target, $position[0]);

            if (abs($distance) < self::CROSS_PRECISION_DEGREES) {
                return [
                    'rc' => SwissDate::OK,
                    'tjd' => $crossing,
                    'error' => '',
                ];
            }

            if ($position[3] == 0.0) {
                return [
                    'rc' => SwissDate::ERR,
                    'tjd' => $tjdEt - 1.0,
                    'error' => 'Heliocentric longitude speed is zero.',
                ];
            }

            $crossing += $distance / $position[3];
        }

        return [
            'rc' => SwissDate::OK,
            'tjd' => $crossing,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, tjd:float, error:string}
     */
    public static function helioCrossUt(
        int   $body,
        float $longitude,
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): array
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $crossing = self::helioCross($body, $longitude, $tjdEt, $flags, $direction);

        if ($crossing['rc'] === SwissDate::ERR) {
            return [
                'rc' => SwissDate::ERR,
                'tjd' => $tjdUt - 1.0,
                'error' => $crossing['error'],
            ];
        }

        $crossing['tjd'] -= DeltaT::deltatEx($crossing['tjd'], $flags);

        return $crossing;
    }

    public static function helioCrossResult(
        int   $body,
        float $longitude,
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): CrossingResult
    {
        return CrossingResult::fromArray(self::helioCross($body, $longitude, $tjdEt, $flags, $direction));
    }

    public static function helioCrossUtResult(
        int   $body,
        float $longitude,
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $direction = 1
    ): CrossingResult
    {
        return CrossingResult::fromArray(self::helioCrossUt($body, $longitude, $tjdUt, $flags, $direction));
    }

    private static function normalizeHeliocentricBody(int $body): int
    {
        if ($body === Catalog::SE_AST_OFFSET + 134340) {
            return Catalog::SE_PLUTO;
        }

        return $body;
    }

    private static function refineMoonNodeCrossing(float $left, float $right, int $flags): float
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $leftLatitude = self::moonLatitude($left, $flags);
            $middleLatitude = self::moonLatitude($middle, $flags);

            if (self::crossedLatitude($leftLatitude, $middleLatitude)) {
                $right = $middle;
            } else {
                $left = $middle;
            }
        }

        return ($left + $right) / 2.0;
    }

    private static function moonLatitude(float $tjdEt, int $flags): float
    {
        try {
            $result = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MOON, $flags);
        } catch (\InvalidArgumentException) {
            return NAN;
        }

        if ($result['rc'] === SwissDate::ERR) {
            return NAN;
        }

        return $result['xx'][1];
    }

    private static function crossedLatitude(float $left, float $right): bool
    {
        if (is_nan($left) || is_nan($right)) {
            return false;
        }

        return $left == 0.0 || $left * $right <= 0.0;
    }

    private static function bodyCross(
        int   $body,
        float $longitude,
        float $tjdEt,
        int   $flags,
        float $stepDays,
        float $maxDays
    ): float
    {
        $flags = Catalog::normalizeEphemerisFlags($flags) | Catalog::SEFLG_SPEED;
        $target = Angle::degnorm($longitude);

        $left = $tjdEt;
        $leftDifference = self::bodyDifference($body, $target, $left, $flags);
        $end = $tjdEt + $maxDays;

        for ($right = $tjdEt + $stepDays; $right <= $end + 1e-12; $right += $stepDays) {
            $rightDifference = self::bodyDifference($body, $target, $right, $flags);

            if (self::crossed($leftDifference, $rightDifference)) {
                return self::refineCrossing($body, $target, $left, $right, $flags);
            }

            $left = $right;
            $leftDifference = $rightDifference;
        }

        return $tjdEt - 1.0;
    }

    private static function refineCrossing(
        int   $body,
        float $target,
        float $left,
        float $right,
        int   $flags
    ): float
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $leftDifference = self::bodyDifference($body, $target, $left, $flags);
            $middleDifference = self::bodyDifference($body, $target, $middle, $flags);

            if (self::crossed($leftDifference, $middleDifference)) {
                $right = $middle;
            } else {
                $left = $middle;
            }
        }

        return ($left + $right) / 2.0;
    }

    private static function bodyDifference(int $body, float $target, float $tjdEt, int $flags): float
    {
        try {
            $result = Calculator::calcApparentFlags($tjdEt, $body, $flags);
        } catch (\InvalidArgumentException) {
            return NAN;
        }

        if ($result['rc'] === SwissDate::ERR) {
            return NAN;
        }

        return Angle::difdeg2n($result['xx'][0], $target);
    }

    private static function crossed(float $left, float $right): bool
    {
        if (is_nan($left) || is_nan($right)) {
            return false;
        }

        return $left == 0.0
            || $left * $right <= 0.0
            || abs($left - $right) > 180.0;
    }
}