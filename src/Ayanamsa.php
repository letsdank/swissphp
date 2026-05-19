<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final class Ayanamsa
{
    private const J2000 = 2451545.0;
    private const J1900 = 2415020.0;
    private const B1950 = 2433282.42345905;
    private const SPEED_INTERVAL = 0.001;

    /**
     * Swiss Ephemeris compatible subset of swe_get_ayanamsa().
     */
    public static function ayanamsa(
        float $tjdEt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): float
    {
        $data = self::modeData($sidMode);

        return self::customAyanamsa(
            $tjdEt,
            $data['t0'],
            $data['ayanT0'],
            $withNutation,
            $data['t0IsUt'],
            $data['model'],
        );
    }

    /**
     * Swiss Ephemeris compatible subset of swe_get_ayanamsa_ut().
     */
    public static function ayanamsaUt(
        float $tjdUt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): float
    {
        return self::ayanamsa(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $withNutation
        );
    }

    /**
     * Swiss Ephemeris compatible ayanamsa speed approximation.
     *
     * @return array{0:float, 1:float} ayanamsa degrees, speed degrees/day
     */
    public static function ayanamsaWithSpeed(
        float $tjdEt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): array
    {
        $ayanamsa = self::ayanamsa($tjdEt, $sidMode, $withNutation);
        $previous = self::ayanamsa($tjdEt - self::SPEED_INTERVAL, $sidMode, $withNutation);

        return [
            $ayanamsa,
            Angle::difdeg2n($ayanamsa, $previous) / self::SPEED_INTERVAL,
        ];
    }

    /**
     * @return array{0:float, 1:float} ayanamsa degrees, speed degrees/day
     */
    public static function ayanamsaUtWithSpeed(
        float $tjdUt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): array
    {
        return self::ayanamsaWithSpeed(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $withNutation
        );
    }

    /**
     * Stateless equivalent of swe_set_sid_mode(SE_SIDM_USER, $t0, $ayanT0)
     * followed by swe_get_ayanamsa().
     */
    public static function customAyanamsa(
        float  $tjdEt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        if ($t0IsUt) {
            $t0 += DeltaT::deltatEx($t0, -1);
        }

        return self::ayanamsaFromEpoch($tjdEt, $t0, $ayanT0, $withNutation, $model);
    }

    /**
     * Stateless equivalent of swe_set_sid_mode(SE_SIDM_USER, $t0, $ayanT0)
     * followed by swe_get_ayanamsa_ut().
     */
    public static function customAyanamsaUt(
        float  $tjdUt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::customAyanamsa(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $t0,
            $ayanT0,
            $withNutation,
            $t0IsUt,
            $model
        );
    }

    /**
     * @return array{0:float, 1:float} ayanamsa degrees, speed degrees/day
     */
    public static function customAyanamsaWithSpeed(
        float  $tjdEt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        $ayanamsa = self::customAyanamsa($tjdEt, $t0, $ayanT0, $withNutation, $t0IsUt, $model);
        $previous = self::customAyanamsa(
            $tjdEt - self::SPEED_INTERVAL,
            $t0,
            $ayanT0,
            $withNutation,
            $t0IsUt,
            $model
        );

        return [
            $ayanamsa,
            Angle::difdeg2n($ayanamsa, $previous) / self::SPEED_INTERVAL,
        ];
    }

    /**
     * @return array{0:float, 1:float} ayanamsa degrees, speed degrees/day
     */
    public static function customAyanamsaUtWithSpeed(
        float  $tjdUt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::customAyanamsaWithSpeed(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $t0,
            $ayanT0,
            $withNutation,
            $t0IsUt,
            $model
        );
    }

    public static function userAyanamsa(
        float  $tjdEt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::customAyanamsa(
            $tjdEt,
            $t0,
            $ayanT0,
            $withNutation,
            Catalog::hasSiderealModeBit($sidMode, Catalog::SE_SIDBIT_USER_UT),
            $model
        );
    }

    public static function userAyanamsaUt(
        float  $tjdUt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::userAyanamsa(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $t0,
            $ayanT0,
            $withNutation,
            $model
        );
    }

    /**
     * @return array{0:float, 1:float}
     */
    public static function userAyanamsaWithSpeed(
        float  $tjdEt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::customAyanamsaWithSpeed(
            $tjdEt,
            $t0,
            $ayanT0,
            $withNutation,
            Catalog::hasSiderealModeBit($sidMode, Catalog::SE_SIDBIT_USER_UT),
            $model
        );
    }

    /**
     * @return array{0:float, 1:float}
     */
    public static function userAyanamsaUtWithSpeed(
        float  $tjdUt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::userAyanamsaWithSpeed(
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $t0,
            $ayanT0,
            $withNutation,
            $model
        );
    }

    public static function userSiderealLongitude(
        float  $tropicalLongitude,
        float  $tjdEt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::applyAyanamsa(
            $tropicalLongitude,
            self::userAyanamsa($tjdEt, $sidMode, $t0, $ayanT0, $withNutation, $model)
        );
    }

    public static function userSiderealLongitudeUt(
        float  $tropicalLongitude,
        float  $tjdUt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::userSiderealLongitude(
            $tropicalLongitude,
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $t0,
            $ayanT0,
            $withNutation,
            $model
        );
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function userSiderealPosition(
        array  $position,
        float  $tjdEt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        if (array_key_exists(3, $position)) {
            [$ayanamsa, $ayanamsaSpeed] = self::userAyanamsaWithSpeed(
                $tjdEt,
                $sidMode,
                $t0,
                $ayanT0,
                $withNutation,
                $model
            );

            return self::applyAyanamsaToPosition($position, $ayanamsa, $ayanamsaSpeed);
        }

        return self::applyAyanamsaToPosition(
            $position,
            self::userAyanamsa($tjdEt, $sidMode, $t0, $ayanT0, $withNutation, $model)
        );
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function userSiderealPositionUt(
        array  $position,
        float  $tjdUt,
        int    $sidMode,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::userSiderealPosition(
            $position,
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $t0,
            $ayanT0,
            $withNutation,
            $model
        );
    }

    public static function customSiderealLongitude(
        float  $tropicalLongitude,
        float  $tjdEt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::applyAyanamsa(
            $tropicalLongitude,
            self::customAyanamsa($tjdEt, $t0, $ayanT0, $withNutation, $t0IsUt, $model)
        );
    }

    public static function customSiderealLongitudeUt(
        float  $tropicalLongitude,
        float  $tjdUt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): float
    {
        return self::applyAyanamsa(
            $tropicalLongitude,
            self::customAyanamsaUt($tjdUt, $t0, $ayanT0, $withNutation, $t0IsUt, $model)
        );
    }

    /**
     * Apply ayanamsa to a Swiss-style position array.
     *
     * If longitude speed is present at index 3, pass ayanamsa speed to convert it too.
     *
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function applyAyanamsaToPosition(
        array  $position,
        float  $ayanamsa,
        ?float $ayanamsaSpeed = null
    ): array
    {
        $result = $position;
        $result[0] = Angle::degnorm($position[0] - $ayanamsa);

        if ($ayanamsaSpeed !== null && array_key_exists(3, $result)) {
            $result[3] = $position[3] - $ayanamsaSpeed;
        }

        return $result;
    }

    /**
     * Remove ayanamsa from a Swiss-style sidereal position array.
     *
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function removeAyanamsaFromPosition(
        array  $position,
        float  $ayanamsa,
        ?float $ayanamsaSpeed = null
    ): array
    {
        $result = $position;
        $result[0] = Angle::degnorm($position[0] + $ayanamsa);

        if ($ayanamsaSpeed !== null && array_key_exists(3, $result)) {
            $result[3] = $position[3] + $ayanamsaSpeed;
        }

        return $result;
    }

    /**
     * Convert a tropical Swiss-style position array to sidereal coordinates.
     *
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function siderealPosition(
        array $position,
        float $tjdEt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): array
    {
        if (array_key_exists(3, $position)) {
            [$ayanamsa, $ayanamsaSpeed] = self::ayanamsaWithSpeed($tjdEt, $sidMode, $withNutation);

            return self::applyAyanamsaToPosition($position, $ayanamsa, $ayanamsaSpeed);
        }

        return self::applyAyanamsaToPosition(
            $position,
            self::ayanamsa($tjdEt, $sidMode, $withNutation)
        );
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function siderealPositionUt(
        array $position,
        float $tjdUt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): array
    {
        return self::siderealPosition(
            $position,
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $sidMode,
            $withNutation
        );
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function customSiderealPosition(
        array  $position,
        float  $tjdEt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        if (array_key_exists(3, $position)) {
            [$ayanamsa, $ayanamsaSpeed] = self::customAyanamsaWithSpeed(
                $tjdEt,
                $t0,
                $ayanT0,
                $withNutation,
                $t0IsUt,
                $model
            );

            return self::applyAyanamsaToPosition($position, $ayanamsa, $ayanamsaSpeed);
        }

        return self::applyAyanamsaToPosition(
            $position,
            self::customAyanamsa($tjdEt, $t0, $ayanT0, $withNutation, $t0IsUt, $model)
        );
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    public static function customSiderealPositionUt(
        array  $position,
        float  $tjdUt,
        float  $t0,
        float  $ayanT0,
        bool   $withNutation = true,
        bool   $t0IsUt = false,
        string $model = Precession::MODEL_IAU_1976
    ): array
    {
        return self::customSiderealPosition(
            $position,
            $tjdUt + DeltaT::deltatEx($tjdUt, -1),
            $t0,
            $ayanT0,
            $withNutation,
            $t0IsUt,
            $model
        );
    }

    public static function siderealLongitude(
        float $tropicalLongitude,
        float $tjdEt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): float
    {
        return self::applyAyanamsa(
            $tropicalLongitude,
            self::ayanamsa($tjdEt, $sidMode, $withNutation)
        );
    }

    public static function siderealLongitudeUt(
        float $tropicalLongitude,
        float $tjdUt,
        int   $sidMode = Catalog::SE_SIDM_FAGAN_BRADLEY,
        bool  $withNutation = true
    ): float
    {
        return self::applyAyanamsa(
            $tropicalLongitude,
            self::ayanamsaUt($tjdUt, $sidMode, $withNutation)
        );
    }

    public static function tropicalLongitude(float $siderealLongitude, float $ayanamsa): float
    {
        return Angle::degnorm($siderealLongitude + $ayanamsa);
    }

    public static function applyAyanamsa(float $tropicalLongitude, float $ayanamsa): float
    {
        return Angle::degnorm($tropicalLongitude - $ayanamsa);
    }

    private static function ayanamsaFromEpoch(
        float  $tjdEt,
        float  $t0Et,
        float  $ayanT0,
        bool   $withNutation,
        string $model
    ): float
    {
        $x = [1.0, 0.0, 0.0];

        if ($tjdEt != self::J2000) {
            $x = Precession::precess($x, $tjdEt, Precession::DIRECTION_TO_J2000, $model);
        }

        $x = Precession::precess($x, $t0Et, Precession::DIRECTION_FROM_J2000, $model);
        $x = Coordinates::coortrf($x, deg2rad(SiderealTime::meanObliquity($t0Et)));
        $polar = Coordinates::cartpol($x);

        $ayanamsa = Angle::degnorm(-rad2deg($polar[0]) + $ayanT0);

        if ($withNutation) {
            $nutation = SiderealTime::nutationApprox($tjdEt);
            $ayanamsa = Angle::degnorm($ayanamsa + $nutation['dpsi']);
        }

        return $ayanamsa;
    }

    /**
     * @return array{t0:float, ayanT0:float, t0IsUt:bool, model:string}
     */
    private static function modeData(int $sidMode): array
    {
        return match (Catalog::siderealMode($sidMode)) {
            Catalog::SE_SIDM_FAGAN_BRADLEY => [
                't0' => 2433282.42346,
                'ayanT0' => 24.042044444,
                't0IsUt' => false,
                'model' => Precession::MODEL_NEWCOMB,
            ],
            Catalog::SE_SIDM_LAHIRI => [
                't0' => 2435553.5,
                'ayanT0' => 23.250182778 - 0.004658035,
                't0IsUt' => false,
                'model' => Precession::MODEL_IAU_1976,
            ],
            Catalog::SE_SIDM_RAMAN => [
                't0' => self::J1900,
                'ayanT0' => 360.0 - 338.98556,
                't0IsUt' => false,
                'model' => Precession::MODEL_NEWCOMB,
            ],
            Catalog::SE_SIDM_KRISHNAMURTI => [
                't0' => self::J1900,
                'ayanT0' => 360.0 - 337.636111,
                't0IsUt' => false,
                'model' => Precession::MODEL_NEWCOMB,
            ],
            Catalog::SE_SIDM_J2000 => [
                't0' => self::J2000,
                'ayanT0' => 0.0,
                't0IsUt' => false,
                'model' => Precession::MODEL_IAU_1976,
            ],
            Catalog::SE_SIDM_J1900 => [
                't0' => self::J1900,
                'ayanT0' => 0.0,
                't0IsUt' => false,
                'model' => Precession::MODEL_IAU_1976,
            ],
            Catalog::SE_SIDM_B1950 => [
                't0' => self::B1950,
                'ayanT0' => 0.0,
                't0IsUt' => false,
                'model' => Precession::MODEL_IAU_1976,
            ],
            43 => [
                't0' => self::J1900,
                'ayanT0' => 22.44597222,
                't0IsUt' => false,
                'model' => Precession::MODEL_NEWCOMB,
            ],
            46 => [
                't0' => 2435553.5,
                'ayanT0' => 23.25 - 0.00464207,
                't0IsUt' => false,
                'model' => Precession::MODEL_NEWCOMB,
            ],
            default => throw new InvalidArgumentException('Unsupported ayanamsa mode.'),
        };
    }
}