<?php

declare(strict_types=1);

namespace SwissEph;

final class EclipticNutation
{
    private const SPEED_INTERVAL = 0.001;

    /**
     * Applies approximate nutation in longitude to ecliptic polar coordinates.
     *
     * This is a compact approximation of the final apparent ecliptic step.
     * Angles and angular speeds are in degrees.
     *
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function apply(array $position, float $tjdEt, bool $withSpeed = true): array
    {
        $nutation = SiderealTime::nutationApprox($tjdEt);

        $position[0] = Angle::degnorm($position[0] + $nutation['dpsi']);

        if ($withSpeed) {
            $previous = SiderealTime::nutationApprox($tjdEt - self::SPEED_INTERVAL);
            $position[3] += ($nutation['dpsi'] - $previous['dpsi']) / self::SPEED_INTERVAL;
        }

        return $position;
    }
}