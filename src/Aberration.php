<?php

declare(strict_types=1);

namespace SwissEph;

final class Aberration
{
    private const PLAN_SPEED_INTERVAL = 0.0001;

    /**
     * Annual aberration for rectangular coordinates.
     *
     * Both vectors are rectangular ecliptic coordinates:
     * [x, y, z, dx, dy, dz], AU and AU/day.
     *
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $earth
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public static function annual(array $position, array $earth, bool $withSpeed = true): array
    {
        $original = $position;
        $position = self::annualPosition($position, $earth);

        if (!$withSpeed) {
            return $position;
        }

        $previous = $original;

        for ($i = 0; $i <= 2; $i++) {
            $previous[$i] = $original[$i] - self::PLAN_SPEED_INTERVAL * $original[$i + 3];
        }

        $previousAberrated = self::annualPosition($previous, $earth);

        for ($i = 0; $i <= 2; $i++) {
            $currentCorrection = $position[$i] - $original[$i];
            $previousCorrection = $previousAberrated[$i] - $previous[$i];

            $position[$i + 3] += ($currentCorrection - $previousCorrection) / self::PLAN_SPEED_INTERVAL;
        }

        return $position;
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $earth
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function annualPosition(array $position, array $earth): array
    {
        $radius = sqrt(
            $position[0] * $position[0]
            + $position[1] * $position[1]
            + $position[2] * $position[2]
        );

        $velocity = [
            $earth[3] * Moshier::LIGHTTIME_AUNIT,
            $earth[4] * Moshier::LIGHTTIME_AUNIT,
            $earth[5] * Moshier::LIGHTTIME_AUNIT,
        ];

        $velocitySquared = $velocity[0] * $velocity[0]
            + $velocity[1] * $velocity[1]
            + $velocity[2] * $velocity[2];

        $beta = sqrt(1.0 - $velocitySquared);

        $projection = (
                $position[0] * $velocity[0]
                + $position[1] * $velocity[1]
                + $position[2] * $velocity[2]
            ) / $radius;

        $factor = 1.0 + $projection / (1.0 + $beta);

        for ($i = 0; $i <= 2; $i++) {
            $position[$i] = ($beta * $position[$i] + $factor * $radius * $velocity[$i])
                / (1.0 + $projection);
        }

        return $position;
    }
}