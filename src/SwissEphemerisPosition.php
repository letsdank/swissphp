<?php

declare(strict_types=1);

namespace SwissEph;

/**
 * Direct position access backed by Swiss Ephemeris `.se1` files.
 *
 * This is a thin adapter over EphemerisFiles::position(). It deliberately
 * exposes cartesian file vectors instead of Swiss-style ecliptic polar output.
 * Higher-level apparent/geocentric transformations will be wired separately.
 */
final class SwissEphemerisPosition
{
    /**
     * Returns `[x, y, z, dx, dy, dz]` from Swiss Ephemeris files.
     *
     * The vector is evaluated from `.se1` Chebyshev segments with reference
     * ellipse handling and rot_back() rotation applied. It is not yet the
     * complete equivalent of swe_calc().
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function cartesian(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        $result = self::cartesianResult($body, $tjdEt, $withSpeed);

        if ($result['rc'] !== Catalog::SE_OK) {
            throw new \InvalidArgumentException($result['error']);
        }

        return $result['vector'];
    }

    /**
     * Returns ecliptic polar coordinates `[longitude, latitude, distance, dLon, dLat, dDist]`.
     *
     * Longitude and latitude are returned in degrees. Speeds are returned in
     * degrees/day for angular components and AU/day for distance.
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function polar(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        $result = self::polarResult($body, $tjdEt, $withSpeed);

        if ($result['rc'] !== Catalog::SE_OK) {
            throw new \InvalidArgumentException($result['error']);
        }

        return $result['xx'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function polarResult(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        $result = self::cartesianResult($body, $tjdEt, $withSpeed);

        if ($result['rc'] !== Catalog::SE_OK) {
            $result['xx'] = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

            return $result;
        }

        $polar = Coordinates::cartpolSp($result['vector']);

        $result['xx'] = [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function polarUtResult(int $body, float $tjdUt, bool $withSpeed = true): array
    {
        return self::polarResult($body, $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SWIEPH), $withSpeed);
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function polarUt(int $body, float $tjdUt, bool $withSpeed = true): array
    {
        $result = self::polarUtResult($body, $tjdUt, $withSpeed);

        if ($result['rc'] !== Catalog::SE_OK) {
            throw new \InvalidArgumentException($result['error']);
        }

        return $result['xx'];
    }

    public static function polarCalculationResult(
        int   $body,
        float $tjdEt,
        bool  $withSpeed = true
    ): CalculationResult
    {
        $result = self::polarResult($body, $tjdEt, $withSpeed);

        return new CalculationResult(
            $result['rc'],
            $result['xx'],
            $result['error']
        );
    }

    public static function polarUtCalculationResult(
        int   $body,
        float $tjdUt,
        bool  $withSpeed = true
    ): CalculationResult
    {
        $result = self::polarUtResult($body, $tjdUt, $withSpeed);

        return new CalculationResult(
            $result['rc'],
            $result['xx'],
            $result['error'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function cartesianResult(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        return EphemerisFiles::position($body, $tjdEt, $withSpeed);
    }

    /**
     * @return array<string, mixed>
     */
    public static function cartesianUtResult(int $body, float $tjdUt, bool $withSpeed = true): array
    {
        return self::cartesianResult($body, $tjdUt + DeltaT::deltatEx($tjdUt, Catalog::SEFLG_SWIEPH), $withSpeed);
    }

    /**
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function cartesianUt(int $body, float $tjdUt, bool $withSpeed = true): array
    {
        $result = self::cartesianUtResult($body, $tjdUt, $withSpeed);

        if ($result['rc'] !== Catalog::SE_OK) {
            throw new \InvalidArgumentException($result['error']);
        }

        return $result['vector'];
    }

    public static function isAvailable(int $body, float $tjdEt): bool
    {
        return self::cartesianResult($body, $tjdEt)['rc'] === Catalog::SE_OK;
    }
}