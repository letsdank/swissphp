<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class Observer
{
    public const MIN_GEOGRAPHIC_ALTITUDE = -500.0;
    public const MAX_GEOGRAPHIC_LATITUDE = 25000.0;

    private const EARTH_RADIUS = 6378136.6;
    private const EARTH_OBLATENESS = 1.0 / 298.25642;
    private const EARTH_ROT_SPEED = 7.2921151467e-5 * 86400.0;
    private const ASTRONOMICAL_UNIT = 1.49597870700e11;

    public function __construct(
        public float $longitude,
        public float $latitude,
        public float $altitude = 0.0
    )
    {
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $geo
     */
    public static function fromArray(array $geo): self
    {
        return new self(
            $geo[0],
            $geo[1],
            $geo[2] ?? 0.0
        );
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    public function toArray(): array
    {
        return [$this->longitude, $this->latitude, $this->altitude];
    }

    public function withAltitude(float $altitude): self
    {
        return new self($this->longitude, $this->latitude, $altitude);
    }

    public function normalizedLongitude(): float
    {
        return Angle::difdeg2n($this->longitude, 0.0);
    }

    public function normalizedLatitude(): float
    {
        return max(-90.0, min(90.0, $this->latitude));
    }

    /**
     * Geocentric observer vector in equatorial coordinates of date.
     *
     * Returns Swiss-style cartesian vector in AU and AU/day:
     * [x, y, z, dx, dy, dz].
     *
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    public function geocentricVector(float $tjdEt, bool $withNutation = true): array
    {
        $tjdUt = $tjdEt - DeltaT::deltatEx($tjdEt, -1);

        $nutation = SiderealTime::nutationApprox($tjdEt);
        $eps = SiderealTime::meanObliquity($tjdEt);
        $nutationLongitude = 0.0;

        if ($withNutation) {
            $eps += $nutation['deps'];
            $nutationLongitude = $nutation['dpsi'];
        }

        $siderealDegrees = SiderealTime::sidtime0($tjdUt, $eps, $nutationLongitude) * 15.0;

        $cosLatitude = cos(deg2rad($this->latitude));
        $sinLatitude = sin(deg2rad($this->latitude));

        $flatteningVector = 1.0 - self::EARTH_OBLATENESS;
        $cc = 1.0 / sqrt(
                $cosLatitude * $cosLatitude
                + $flatteningVector * $flatteningVector * $sinLatitude * $sinLatitude
            );
        $ss = $flatteningVector * $flatteningVector * $cc;

        $localSiderealLongitude = deg2rad($this->longitude + $siderealDegrees);
        $cosLongitude = cos($localSiderealLongitude);
        $sinLongitude = sin($localSiderealLongitude);

        $x = (self::EARTH_RADIUS * $cc + $this->altitude) * $cosLatitude * $cosLongitude;
        $y = (self::EARTH_RADIUS * $cc + $this->altitude) * $cosLatitude * $sinLongitude;
        $z = (self::EARTH_RADIUS * $ss + $this->altitude) * $sinLatitude;

        $polar = Coordinates::cartpol([$x, $y, $z]);
        $polarWithSpeed = [
            $polar[0],
            $polar[1],
            $polar[2],
            self::EARTH_ROT_SPEED,
            0.0,
            0.0,
        ];

        $cartesian = Coordinates::polcartSp($polarWithSpeed);

        return [
            $cartesian[0] / self::ASTRONOMICAL_UNIT,
            $cartesian[1] / self::ASTRONOMICAL_UNIT,
            $cartesian[2] / self::ASTRONOMICAL_UNIT,
            $cartesian[3] / self::ASTRONOMICAL_UNIT,
            $cartesian[4] / self::ASTRONOMICAL_UNIT,
            $cartesian[5] / self::ASTRONOMICAL_UNIT,
        ];
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    public function geocentricPosition(float $tjdEt, bool $withNutation = true): array
    {
        $vector = $this->geocentricVector($tjdEt, $withNutation);

        return [$vector[0], $vector[1], $vector[2]];
    }
}