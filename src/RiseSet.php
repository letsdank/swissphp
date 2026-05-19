<?php

declare(strict_types=1);

namespace SwissEph;

final class RiseSet
{
    private const DEFAULT_STEP_DAYS = 1.0 / 48.0;
    private const DEFAULT_MAX_DAYS = 2.0;
    private const BISECTION_ITERATIONS = 60;

    private const HORIZON_REFRACTION_DEGREES = 34.0 / 60.0;

    private const BODY_RADII_AU = [
        Catalog::SE_SUN => 0.004650467260962157,
        Catalog::SE_MOON => 0.000011613064615868,
        Catalog::SE_MERCURY => 0.000016308497599097,
        Catalog::SE_VENUS => 0.000040454452429379,
        Catalog::SE_MARS => 0.000022657411812496,
        Catalog::SE_JUPITER => 0.000467327740035957,
        Catalog::SE_SATURN => 0.000389258215026448,
        Catalog::SE_URANUS => 0.000169535835006230,
        Catalog::SE_NEPTUNE => 0.000164589237339572,
        Catalog::SE_PLUTO => 0.000007943305401144,
    ];

    private const FIXED_DISC_DISTANCE_AU = [
        Catalog::SE_SUN => 1.0,
        Catalog::SE_MOON => 0.00257,
    ];

    /**
     * Finds next rise of fixed equatorial coordinates.
     *
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextRise(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $horizonAltitude = 0.0,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextCrossing(
            $tjdUt,
            $observer,
            $equatorial,
            $horizonAltitude,
            $pressure,
            $temperature,
            $stepDays,
            $maxDays,
            true
        );
    }

    /**
     * Finds next set of fixed equatorial coordinates.
     *
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextSet(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $horizonAltitude = 0.0,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextCrossing(
            $tjdUt,
            $observer,
            $equatorial,
            $horizonAltitude,
            $pressure,
            $temperature,
            $stepDays,
            $maxDays,
            false
        );
    }

    /**
     * Finds next upper meridian transit of fixed equatorial coordinates.
     *
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextUpperTransit(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        float    $stepDays = 1.0 / 96.0,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextTransit(
            $tjdUt,
            $observer,
            $equatorial,
            $pressure,
            $temperature,
            $stepDays,
            $maxDays,
            true
        );
    }

    /**
     * Finds next lower meridian transit of fixed equatorial coordinates.
     *
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextLowerTransit(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        float    $stepDays = 1.0 / 96.0,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextTransit(
            $tjdUt,
            $observer,
            $equatorial,
            $pressure,
            $temperature,
            $stepDays,
            $maxDays,
            false
        );
    }

    /**
     * Finds next rise of a supported body using apparent equatorial coordinates.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextBodyRise(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $horizonAltitude = 0.0,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextBodyCrossing(
            $tjdUt,
            $body,
            $observer,
            $horizonAltitude,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays,
            true
        );
    }

    /**
     * Finds next set of a supported body using apparent equatorial coordinates.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextBodySet(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $horizonAltitude = 0.0,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextBodyCrossing(
            $tjdUt,
            $body,
            $observer,
            $horizonAltitude,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays,
            false
        );
    }

    /**
     * Finds next upper meridian transit of a supported body.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextBodyUpperTransit(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = 1.0 / 96.0,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextBodyTransit(
            $tjdUt,
            $body,
            $observer,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays,
            true
        );
    }

    /**
     * Finds next lower meridian transit of a supported body.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function nextBodyLowerTransit(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = 1.0 / 96.0,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        return self::nextBodyTransit(
            $tjdUt,
            $body,
            $observer,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays,
            false
        );
    }

    /**
     * Swiss-like facade for rise, set, and meridian transit of a supported body.
     *
     * If horizon altitude is omitted, rise/set uses apparent upper limb by default:
     * body semidiameter plus standard horizon refraction.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function riseTrans(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        int      $eventFlag,
        ?float   $horizonAltitude = null,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        self::assertObserverAltitudeRange($observer);

        $eventFlag = self::normalizeRiseSetEventFlag($eventFlag);

        $horizonAltitude = self::eventHorizonAltitude(
            $tjdUt,
            $body,
            $eventFlag,
            $horizonAltitude,
            $flags
        );

        if (($eventFlag & Catalog::SE_CALC_RISE) !== 0) {
            return self::nextBodyCrossing(
                $tjdUt,
                $body,
                $observer,
                $horizonAltitude,
                $pressure,
                $temperature,
                $flags,
                $stepDays,
                $maxDays,
                true,
                $eventFlag
            );
        }

        if (($eventFlag & Catalog::SE_CALC_SET) !== 0) {
            return self::nextBodyCrossing(
                $tjdUt,
                $body,
                $observer,
                $horizonAltitude,
                $pressure,
                $temperature,
                $flags,
                $stepDays,
                $maxDays,
                false,
                $eventFlag
            );
        }

        $transitStepDays = min($stepDays, 1.0 / 96.0);

        if (($eventFlag & Catalog::SE_CALC_MTRANSIT) !== 0) {
            return self::nextBodyUpperTransit(
                $tjdUt,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags,
                $transitStepDays,
                $maxDays
            );
        }

        if (($eventFlag & Catalog::SE_CALC_ITRANSIT) !== 0) {
            return self::nextBodyLowerTransit(
                $tjdUt,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags,
                $transitStepDays,
                $maxDays
            );
        }

        return null;
    }

    /**
     * Swiss-like facade for rise, set, and meridian transit with explicit true horizon height.
     *
     * The true horizon height is added before disc/refraction handling. Twilight flags ignore
     * local horizon height, matching the Swiss Ephemeris convention.
     *
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    public static function riseTransTrueHorizon(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        int      $eventFlag,
        float    $trueHorizonHeight,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?array
    {
        self::assertObserverAltitudeRange($observer);

        if ($trueHorizonHeight == -100.0) {
            $trueHorizonHeight = 0.0001 + Refraction::horizonDip(
                    $observer->altitude,
                    $pressure,
                    $temperature
                );
        }

        $horizonAltitude = self::eventHorizonAltitudeFromBase(
            $tjdUt,
            $body,
            $eventFlag,
            $trueHorizonHeight,
            $flags
        );

        return self::riseTrans(
            $tjdUt,
            $body,
            $observer,
            $eventFlag,
            $horizonAltitude,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays
        );
    }

    public static function riseTransResult(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        int      $eventFlag,
        ?float   $horizonAltitude = null,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?RiseSetResult
    {
        $event = self::riseTrans(
            $tjdUt,
            $body,
            $observer,
            $eventFlag,
            $horizonAltitude,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays
        );

        return $event === null ? null : RiseSetResult::fromArray($event);
    }

    public static function riseTransTrueHorizonResult(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        int      $eventFlag,
        float    $trueHorizonHeight,
        float    $pressure = 1013.25,
        float    $temperature = 15.0,
        int      $flags = Catalog::SEFLG_SPEED,
        float    $stepDays = self::DEFAULT_STEP_DAYS,
        float    $maxDays = self::DEFAULT_MAX_DAYS
    ): ?RiseSetResult
    {
        $event = self::riseTransTrueHorizon(
            $tjdUt,
            $body,
            $observer,
            $eventFlag,
            $trueHorizonHeight,
            $pressure,
            $temperature,
            $flags,
            $stepDays,
            $maxDays
        );

        return $event === null ? null : RiseSetResult::fromArray($event);
    }

    private static function assertObserverAltitudeRange(Observer $observer): void
    {
        if (
            $observer->altitude < Observer::MIN_GEOGRAPHIC_ALTITUDE
            || $observer->altitude > Observer::MAX_GEOGRAPHIC_LATITUDE
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Location for rise/transit calculations must be between %.0f and %.0f m above sea.',
                Observer::MIN_GEOGRAPHIC_ALTITUDE,
                Observer::MAX_GEOGRAPHIC_LATITUDE
            ));
        }
    }

    private static function normalizeRiseSetEventFlag(int $eventFlag): int
    {
        $eventMask = Catalog::SE_CALC_RISE
            | Catalog::SE_CALC_SET
            | Catalog::SE_CALC_MTRANSIT
            | Catalog::SE_CALC_ITRANSIT;

        if (($eventFlag & $eventMask) === 0) {
            return $eventFlag | Catalog::SE_CALC_RISE;
        }

        return $eventFlag;
    }

    private static function eventHorizonAltitude(
        float  $tjdUt,
        int    $body,
        int    $eventFlag,
        ?float $default,
        int    $flags
    ): float
    {
        if (($eventFlag & Catalog::SE_BIT_CIVIL_TWILIGHT) !== 0) {
            return -6.0;
        }

        if (($eventFlag & Catalog::SE_BIT_NAUTIC_TWILIGHT) !== 0) {
            return -12.0;
        }

        if (($eventFlag & Catalog::SE_BIT_ASTRO_TWILIGHT) !== 0) {
            return -18.0;
        }

        if ($default !== null) {
            return $default;
        }

        return self::eventHorizonAltitudeFromBase(
            $tjdUt,
            $body,
            $eventFlag,
            0.0,
            $flags
        );
    }

    private static function eventHorizonAltitudeFromBase(
        float $tjdUt,
        int   $body,
        int   $eventFlag,
        float $baseHorizonAltitude,
        int   $flags
    ): float
    {
        if (($eventFlag & Catalog::SE_BIT_CIVIL_TWILIGHT) !== 0) {
            return -6.0;
        }

        if (($eventFlag & Catalog::SE_BIT_NAUTIC_TWILIGHT) !== 0) {
            return -12.0;
        }

        if (($eventFlag & Catalog::SE_BIT_ASTRO_TWILIGHT) !== 0) {
            return -18.0;
        }

        $radius = (($eventFlag & Catalog::SE_BIT_DISC_CENTER) !== 0)
            ? 0.0
            : self::apparentBodyRadius($tjdUt, $body, $flags, $eventFlag);

        if (($eventFlag & Catalog::SE_BIT_DISC_BOTTOM) !== 0) {
            $horizon = $baseHorizonAltitude + $radius;
        } else {
            $horizon = $baseHorizonAltitude - $radius;
        }

        if (($eventFlag & Catalog::SE_BIT_NO_REFRACTION) === 0) {
            $horizon -= self::HORIZON_REFRACTION_DEGREES;
        }

        return $horizon;
    }

    private static function apparentBodyRadius(float $tjdUt, int $body, int $flags, int $eventFlag): float
    {
        if (!array_key_exists($body, self::BODY_RADII_AU)) {
            return 0.0;
        }

        if (($eventFlag & Catalog::SE_BIT_FIXED_DISC_SIZE) !== 0 && array_key_exists($body, self::FIXED_DISC_DISTANCE_AU)) {
            return rad2deg(asin(self::BODY_RADII_AU[$body] / self::FIXED_DISC_DISTANCE_AU[$body]));
        }

        $calcFlags = ($flags | Catalog::SEFLG_SPEED) & ~Catalog::SEFLG_XYZ & ~Catalog::SEFLG_RADIANS;
        $result = Calculator::calcApparentFlagsUt($tjdUt, $body, $calcFlags);

        if ($result['rc'] === SwissDate::ERR || $result['xx'][2] <= 0.0) {
            return 0.0;
        }

        return rad2deg(asin(self::BODY_RADII_AU[$body] / $result['xx'][2]));
    }

    /**
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    private static function nextBodyCrossing(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $horizonAltitude,
        float    $pressure,
        float    $temperature,
        int      $flags,
        float    $stepDays,
        float    $maxDays,
        bool     $rise,
        int      $eventFlag = 0
    ): ?array
    {
        $previousTime = $tjdUt;
        $previousAltitude = self::bodyTrueAltitude(
                $previousTime,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags,
                $eventFlag
            ) - $horizonAltitude;

        $end = $tjdUt + $maxDays;

        for ($time = $tjdUt + $stepDays; $time <= $end + 1e-12; $time += $stepDays) {
            $currentAltitude = self::bodyTrueAltitude(
                    $time,
                    $body,
                    $observer,
                    $pressure,
                    $temperature,
                    $flags,
                    $eventFlag
                ) - $horizonAltitude;

            $crossed = $rise
                ? ($previousAltitude < 0.0 && $currentAltitude >= 0.0)
                : ($previousAltitude > 0.0 && $currentAltitude <= 0.0);

            if ($crossed) {
                return self::refineBodyCrossing(
                    $previousTime,
                    $time,
                    $body,
                    $observer,
                    $horizonAltitude,
                    $pressure,
                    $temperature,
                    $flags,
                    $rise,
                    $eventFlag
                );
            }

            $previousTime = $time;
            $previousAltitude = $currentAltitude;
        }

        return null;
    }

    /**
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}
     */
    private static function refineBodyCrossing(
        float    $left,
        float    $right,
        int      $body,
        Observer $observer,
        float    $horizonAltitude,
        float    $pressure,
        float    $temperature,
        int      $flags,
        bool     $rise,
        int      $eventFlag = 0
    ): array
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $altitude = self::bodyTrueAltitude(
                    $middle,
                    $body,
                    $observer,
                    $pressure,
                    $temperature,
                    $flags,
                    $eventFlag
                ) - $horizonAltitude;

            if ($rise) {
                if ($altitude >= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            } else {
                if ($altitude <= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            }
        }

        $time = ($left + $right) / 2.0;
        $horizontal = self::bodyHorizontal($time, $body, $observer, $pressure, $temperature, $flags, $eventFlag);

        return [
            'tjdUt' => $time,
            'azimuth' => $horizontal[0],
            'trueAltitude' => $horizontal[1],
            'apparentAltitude' => $horizontal[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    private static function nextCrossing(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $horizonAltitude,
        float    $pressure,
        float    $temperature,
        float    $stepDays,
        float    $maxDays,
        bool     $rise
    ): ?array
    {
        $previousTime = $tjdUt;
        $previousAltitude = self::trueAltitude(
                $previousTime,
                $observer,
                $equatorial,
                $pressure,
                $temperature
            ) - $horizonAltitude;

        $end = $tjdUt + $maxDays;

        for ($time = $tjdUt + $stepDays; $time <= $end + 1e-12; $time += $stepDays) {
            $currentAltitude = self::trueAltitude(
                    $time,
                    $observer,
                    $equatorial,
                    $pressure,
                    $temperature
                ) - $horizonAltitude;

            $crossed = $rise
                ? ($previousAltitude < 0.0 && $currentAltitude >= 0.0)
                : ($previousAltitude > 0.0 && $currentAltitude <= 0.0);

            if ($crossed) {
                return self::refineCrossing(
                    $previousTime,
                    $time,
                    $observer,
                    $equatorial,
                    $horizonAltitude,
                    $pressure,
                    $temperature,
                    $rise
                );
            }

            $previousTime = $time;
            $previousAltitude = $currentAltitude;
        }

        return null;
    }

    /**
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    private static function nextBodyTransit(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        int      $flags,
        float    $stepDays,
        float    $maxDays,
        bool     $upper
    ): ?array
    {
        $previousTime = $tjdUt;
        $previousDerivative = self::bodyAltitudeDerivative(
            $previousTime,
            $body,
            $observer,
            $pressure,
            $temperature,
            $flags,
            $stepDays
        );

        $end = $tjdUt + $maxDays;

        for ($time = $tjdUt + $stepDays; $time <= $end + 1e-12; $time += $stepDays) {
            $derivative = self::bodyAltitudeDerivative(
                $time,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags,
                $stepDays
            );

            $crossed = $upper
                ? ($previousDerivative > 0.0 && $derivative <= 0.0)
                : ($previousDerivative < 0.0 && $derivative >= 0.0);

            if ($crossed) {
                return self::refineBodyTransit(
                    $previousTime,
                    $time,
                    $body,
                    $observer,
                    $pressure,
                    $temperature,
                    $flags,
                    $stepDays,
                    $upper
                );
            }

            $previousTime = $time;
            $previousDerivative = $derivative;
        }

        return null;
    }

    /**
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}
     */
    private static function refineBodyTransit(
        float    $left,
        float    $right,
        int      $body,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        int      $flags,
        float    $stepDays,
        bool     $upper
    ): array
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $derivative = self::bodyAltitudeDerivative(
                $middle,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags,
                $stepDays
            );

            if ($upper) {
                if ($derivative <= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            } else {
                if ($derivative >= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            }
        }

        $time = ($left + $right) / 2.0;
        $horizontal = self::bodyHorizontal($time, $body, $observer, $pressure, $temperature, $flags);

        return [
            'tjdUt' => $time,
            'azimuth' => $horizontal[0],
            'trueAltitude' => $horizontal[1],
            'apparentAltitude' => $horizontal[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}|null
     */
    private static function nextTransit(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $pressure,
        float    $temperature,
        float    $stepDays,
        float    $maxDays,
        bool     $upper
    ): ?array
    {
        $previousTime = $tjdUt;
        $previousDerivative = self::altitudeDerivative(
            $previousTime,
            $observer,
            $equatorial,
            $pressure,
            $temperature,
            $stepDays
        );

        $end = $tjdUt + $maxDays;

        for ($time = $tjdUt + $stepDays; $time <= $end + 1e-12; $time += $stepDays) {
            $derivative = self::altitudeDerivative(
                $time,
                $observer,
                $equatorial,
                $pressure,
                $temperature,
                $stepDays
            );

            $crossed = $upper
                ? ($previousDerivative > 0.0 && $derivative <= 0.0)
                : ($previousDerivative < 0.0 && $derivative >= 0.0);

            if ($crossed) {
                return self::refineTransit(
                    $previousTime,
                    $time,
                    $observer,
                    $equatorial,
                    $pressure,
                    $temperature,
                    $stepDays,
                    $upper
                );
            }

            $previousTime = $time;
            $previousDerivative = $derivative;
        }

        return null;
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}
     */
    private static function refineTransit(
        float    $left,
        float    $right,
        Observer $observer,
        array    $equatorial,
        float    $pressure,
        float    $temperature,
        float    $stepDays,
        bool     $upper
    ): array
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $derivative = self::altitudeDerivative(
                $middle,
                $observer,
                $equatorial,
                $pressure,
                $temperature,
                $stepDays
            );

            if ($upper) {
                if ($derivative <= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            } else {
                if ($derivative >= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            }
        }

        $time = ($left + $right) / 2.0;

        $horizontal = AzimuthAltitude::azalt(
            $time,
            Catalog::SE_EQU2HOR,
            $observer,
            $pressure,
            $temperature,
            $equatorial
        );

        return [
            'tjdUt' => $time,
            'azimuth' => $horizontal[0],
            'trueAltitude' => $horizontal[1],
            'apparentAltitude' => $horizontal[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2?: float} $equatorial
     */
    private static function altitudeDerivative(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $pressure,
        float    $temperature,
        float    $stepDays
    ): float
    {
        return self::trueAltitude(
                $tjdUt + $stepDays,
                $observer,
                $equatorial,
                $pressure,
                $temperature
            )
            - self::trueAltitude(
                $tjdUt - $stepDays,
                $observer,
                $equatorial,
                $pressure,
                $temperature
            );
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $equatorial
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}
     */
    private static function refineCrossing(
        float    $left,
        float    $right,
        Observer $observer,
        array    $equatorial,
        float    $horizonAltitude,
        float    $pressure,
        float    $temperature,
        bool     $rise
    ): array
    {
        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $middle = ($left + $right) / 2.0;
            $altitude = self::trueAltitude(
                    $middle,
                    $observer,
                    $equatorial,
                    $pressure,
                    $temperature
                ) - $horizonAltitude;

            if ($rise) {
                if ($altitude >= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            } else {
                if ($altitude <= 0.0) {
                    $right = $middle;
                } else {
                    $left = $middle;
                }
            }
        }

        $time = ($left + $right) / 2.0;

        $horizontal = AzimuthAltitude::azalt(
            $time,
            Catalog::SE_EQU2HOR,
            $observer,
            $pressure,
            $temperature,
            $equatorial
        );

        return [
            'tjdUt' => $time,
            'azimuth' => $horizontal[0],
            'trueAltitude' => $horizontal[1],
            'apparentAltitude' => $horizontal[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2?:float} $equatorial
     */
    private static function trueAltitude(
        float    $tjdUt,
        Observer $observer,
        array    $equatorial,
        float    $pressure,
        float    $temperature
    ): float
    {
        return AzimuthAltitude::azalt(
            $tjdUt,
            Catalog::SE_EQU2HOR,
            $observer,
            $pressure,
            $temperature,
            $equatorial
        )[1];
    }

    private static function bodyTrueAltitude(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        int      $flags,
        int      $eventFlag = 0
    ): float
    {
        return self::bodyHorizontal($tjdUt, $body, $observer, $pressure, $temperature, $flags, $eventFlag)[1];
    }

    private static function bodyAltitudeDerivative(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        int      $flags,
        float    $stepDays
    ): float
    {
        return self::bodyTrueAltitude(
                $tjdUt + $stepDays,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags
            )
            - self::bodyTrueAltitude(
                $tjdUt - $stepDays,
                $body,
                $observer,
                $pressure,
                $temperature,
                $flags
            );
    }

    /**
     * @return array{0:float, 1:float, 2:float}
     */
    private static function bodyHorizontal(
        float    $tjdUt,
        int      $body,
        Observer $observer,
        float    $pressure,
        float    $temperature,
        int      $flags,
        int      $eventFlag = 0
    ): array
    {
        $calcFlags = ($flags | Catalog::SEFLG_SPEED) & ~Catalog::SEFLG_XYZ & ~Catalog::SEFLG_RADIANS;

        if (($eventFlag & Catalog::SE_BIT_GEOCTR_NO_ECL_LAT) !== 0) {
            $result = Calculator::calcApparentFlagsUt(
                $tjdUt,
                $body,
                $calcFlags & ~Catalog::SEFLG_EQUATORIAL
            );

            if ($result['rc'] === SwissDate::ERR) {
                return [0.0, 0.0, 0.0];
            }

            return AzimuthAltitude::azalt(
                $tjdUt,
                Catalog::SE_ECL2HOR,
                $observer,
                $pressure,
                $temperature,
                [$result['xx'][0], 0.0, $result['xx'][2]]
            );
        }

        $result = Calculator::calcApparentFlagsUt(
            $tjdUt,
            $body,
            $calcFlags | Catalog::SEFLG_EQUATORIAL
        );

        if ($result['rc'] === SwissDate::ERR) {
            return [0.0, 0.0, 0.0];
        }

        return AzimuthAltitude::azalt(
            $tjdUt,
            Catalog::SE_EQU2HOR,
            $observer,
            $pressure,
            $temperature,
            [$result['xx'][0], $result['xx'][1], $result['xx'][2]]
        );
    }
}