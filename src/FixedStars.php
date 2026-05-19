<?php

declare(strict_types=1);

namespace SwissEph;

final class FixedStars
{
    private const J2000 = 2451545.0;
    private const DAYS_PER_JULIAN_YEAR = 365.25;
    private const SPEED_INTERVAL = 0.001;
    private const PARSEC_TO_AUNIT = 206264.8062471;

    /**
     * ra/dec: degrees, J2000 equatorial.
     * pmRa/pmDec: mas/year.
     * parallax: mas.
     *
     * @var array<string, array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}>
     */
    private const CATALOG = [
        'sirius' => [
            'name' => 'Sirius',
            'aliases' => ['alpha canis majoris', 'alf cmj', 'dog star'],
            'ra' => 101.28715533,
            'dec' => -16.71611586,
            'pmRa' => -546.01,
            'pmDec' => -1223.07,
            'parallax' => 379.21,
            'mag' => -1.46,
        ],
        'aldebaran' => [
            'name' => 'Aldebaran',
            'aliases' => ['alpha tauri', 'alf tau'],
            'ra' => 68.98016279,
            'dec' => 16.50930235,
            'pmRa' => 62.78,
            'pmDec' => -188.94,
            'parallax' => 50.09,
            'mag' => 0.85,
        ],
        'regulus' => [
            'name' => 'Regulus',
            'aliases' => ['alpha leonis', 'alf leo'],
            'ra' => 152.09296244,
            'dec' => 11.96720878,
            'pmRa' => -249.40,
            'pmDec' => 4.91,
            'parallax' => 41.13,
            'mag' => 1.35,
        ],
        'spica' => [
            'name' => 'Spica',
            'aliases' => ['alpha virginis', 'alf vir'],
            'ra' => 201.29824709,
            'dec' => -11.16132203,
            'pmRa' => -42.35,
            'pmDec' => -31.73,
            'parallax' => 13.06,
            'mag' => 0.98,
        ],
    ];

    /**
     * Swiss Ephemeris compatible subset of swe_fixstar().
     *
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    public static function fixstar(
        string $name,
        float  $tjdEt,
        int    $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        $star = self::find($name);

        if ($star === null) {
            return self::errorResult(sprintf('fixed star "%s" not found', $name));
        }

        $position = self::position($star, $tjdEt, $flags);
        $previous = self::position($star, $tjdEt - self::SPEED_INTERVAL, $flags);

        $xx = [
            $position[0],
            $position[1],
            $position[2],
            Angle::difdeg2n($position[0], $previous[0]) / self::SPEED_INTERVAL,
            ($position[1] - $previous[1]) / self::SPEED_INTERVAL,
            ($position[2] - $previous[2]) / self::SPEED_INTERVAL,
        ];

        $xx = self::finalizeVector($xx, $flags);

        return [
            'rc' => Catalog::normalizeEphemerisFlags($flags),
            'xx' => $xx,
            'star' => $star['name'],
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    public static function fixstarUt(
        string $name,
        float  $tjdUt,
        int    $flags = Catalog::SEFLG_DEFAULTEPH
    ): array
    {
        return self::fixstar(
            $name,
            $tjdUt + DeltaT::deltatEx($tjdUt, $flags),
            $flags
        );
    }

    public static function fixstarResult(
        string $name,
        float  $tjdEt,
        int    $flags = Catalog::SEFLG_DEFAULTEPH
    ): FixedStarResult
    {
        return FixedStarResult::fromArray(self::fixstar($name, $tjdEt, $flags));
    }

    public static function fixstarUtResult(
        string $name,
        float  $tjdUt,
        int    $flags = Catalog::SEFLG_DEFAULTEPH
    ): FixedStarResult
    {
        return FixedStarResult::fromArray(self::fixstarUt($name, $tjdUt, $flags));
    }

    /**
     * @return array{rc:int, mag:float, star:string, error:string}
     */
    public static function fixstarMagnitude(string $name): array
    {
        $star = self::find($name);

        if ($star === null) {
            return [
                'rc' => SwissDate::ERR,
                'mag' => 0.0,
                'star' => '',
                'error' => sprintf('fixed star "%s" not found', $name),
            ];
        }

        return [
            'rc' => SwissDate::OK,
            'mag' => $star['mag'],
            'star' => $star['name'],
            'error' => '',
        ];
    }

    public static function fixstarMagnitudeResult(string $name): FixedStarMagnitudeResult
    {
        return FixedStarMagnitudeResult::fromArray(self::fixstarMagnitude($name));
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(
            static fn(array $star): string => $star['name'],
            array_values(self::CATALOG)
        );
    }

    public static function exists(string $name): bool
    {
        return self::find($name) !== null;
    }

    /**
     * @param array{name:string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float} $star
     * @return array{0:float, 1:float, 2:float}
     */
    private static function position(array $star, float $tjdEt, int $flags): array
    {
        $years = ($tjdEt - self::J2000) / self::DAYS_PER_JULIAN_YEAR;
        $dec = $star['dec'] + $star['pmDec'] / 3600000.0 * $years;
        $cosDec = max(1e-12, cos(deg2rad($star['dec'])));
        $ra = Angle::degnorm($star['ra'] + $star['pmRa'] / 3600000.0 / $cosDec * $years);
        $distance = self::PARSEC_TO_AUNIT / ($star['parallax'] / 1000.0);

        $cartesian = Coordinates::polcart([
            deg2rad($ra),
            deg2rad($dec),
            $distance,
        ]);

        if (!Catalog::hasFlag($flags, Catalog::SEFLG_J2000)) {
            $cartesian = Precession::precess(
                $cartesian,
                $tjdEt,
                Precession::DIRECTION_FROM_J2000,
                Precession::MODEL_IAU_1976
            );
        }

        if (Catalog::hasFlag($flags, Catalog::SEFLG_EQUATORIAL)) {
            $polar = Coordinates::cartpol($cartesian);

            return [
                Angle::degnorm(rad2deg($polar[0])),
                rad2deg($polar[1]),
                $polar[2],
            ];
        }

        $eps = SiderealTime::meanObliquity($tjdEt);
        $cartesian = Coordinates::coortrf($cartesian, deg2rad($eps));
        $polar = Coordinates::cartpol($cartesian);

        $position = [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
            0.0,
            0.0,
            0.0,
        ];

        if (!Catalog::hasFlag($flags, Catalog::SEFLG_NONUT) && !Catalog::hasFlag($flags, Catalog::SEFLG_J2000)) {
            $position = EclipticNutation::apply($position, $tjdEt, false);
        }

        return [$position[0], $position[1], $position[2]];
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function finalizeVector(array $position, int $flags): array
    {
        $withSpeed = Catalog::wantsSpeed($flags);

        if (!$withSpeed) {
            $position[3] = 0.0;
            $position[4] = 0.0;
            $position[5] = 0.0;
        }

        if (Catalog::hasFlag($flags, Catalog::SEFLG_XYZ)) {
            $position[0] = deg2rad($position[0]);
            $position[1] = deg2rad($position[1]);
            $position[3] = $withSpeed ? deg2rad($position[3]) : 0.0;
            $position[4] = $withSpeed ? deg2rad($position[4]) : 0.0;
            $position[5] = $withSpeed ? $position[5] : 0.0;

            return Coordinates::polcartSp($position);
        }

        if (Catalog::hasFlag($flags, Catalog::SEFLG_RADIANS)) {
            $position[0] = deg2rad($position[0]);
            $position[1] = deg2rad($position[1]);
            $position[3] = deg2rad($position[3]);
            $position[4] = deg2rad($position[4]);
        }

        return $position;
    }

    /**
     * @return array{name:string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}|null
     */
    private static function find(string $name): ?array
    {
        $key = self::normalizeName($name);

        if (isset(self::CATALOG[$key])) {
            return self::CATALOG[$key];
        }

        foreach (self::CATALOG as $star) {
            foreach ($star['aliases'] as $alias) {
                if (self::normalizeName($alias) === $key) {
                    return $star;
                }
            }
        }

        return null;
    }

    private static function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(['_', '-'], ' ', $name);

        return preg_replace('/\s+/', ' ', $name) ?? $name;
    }

    /**
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    private static function errorResult(string $error): array
    {
        return [
            'rc' => SwissDate::ERR,
            'xx' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'star' => '',
            'error' => $error,
        ];
    }
}