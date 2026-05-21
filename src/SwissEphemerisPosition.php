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
     * @return array<string, mixed>
     */
    public static function cartesianResult(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        return EphemerisFiles::position($body, $tjdEt, $withSpeed);
    }

    public static function isAvailable(int $body, float $tjdEt): bool
    {
        return self::cartesianResult($body, $tjdEt)['rc'] === Catalog::SE_OK;
    }
}