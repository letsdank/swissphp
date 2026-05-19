<?php

declare(strict_types=1);

namespace SwissEph;

final class NodesApsides
{
    private const SPEED_INTERVAL = 0.001;
    private const OSCULATING_INTERVAL_FACTOR = 0.001;
    private const AUNIT = 149597870700.0;
    private const HELGRAVCONST = 1.32712440017987e20;

    /** @var array<int, float> */
    private const PLANET_MASS_RATIO = [
        Catalog::SE_MERCURY => 6023600.0,
        Catalog::SE_VENUS => 408523.719,
        Catalog::SE_MARS => 3098703.59,
        Catalog::SE_JUPITER => 1047.348644,
        Catalog::SE_SATURN => 3497.9018,
        Catalog::SE_URANUS => 22902.98,
        Catalog::SE_NEPTUNE => 19412.26,
        Catalog::SE_PLUTO => 136566000.0,
    ];

    /** @var array<int, int> */
    private const PLANET_ELEMENT_INDEX = [
        Catalog::SE_MERCURY => 0,
        Catalog::SE_VENUS => 1,
        Catalog::SE_EARTH => 2,
        Catalog::SE_MARS => 3,
        Catalog::SE_JUPITER => 4,
        Catalog::SE_SATURN => 5,
        Catalog::SE_URANUS => 6,
        Catalog::SE_NEPTUNE => 7,
    ];

    /** @var array<int, array{0:float, 1:float, 2:float, 3:float}> */
    private const ELEMENT_NODE = [
        [48.330893, 1.1861890, 0.00017587, 0.000000211],
        [76.679920, 0.9011190, 0.00040665, -0.000000080],
        [0.0, 0.0, 0.0, 0.0],
        [49.558093, 0.7720923, 0.00001605, 0.000002325],
        [100.464441, 1.0209550, 0.00040117, 0.000000569],
        [113.665524, 0.8770970, -0.00012067, -0.000002380],
        [74.005947, 0.5211258, 0.00133982, 0.000018516],
        [131.784057, 1.1022057, 0.00026006, -0.000000636],
    ];

    /** @var array<int, array{0:float, 1:float, 2:float, 3:float}> */
    private const ELEMENT_PERIHELION = [
        [77.456119, 1.5564775, 0.00029589, 0.000000056],
        [131.563707, 1.4022188, -0.00107337, -0.000005315],
        [102.937348, 1.7195269, 0.00045962, 0.000000499],
        [336.060234, 1.8410331, 0.00013515, 0.000000318],
        [14.331309, 1.6126668, 0.00103127, -0.000004569],
        [93.056787, 1.9637694, 0.00083757, 0.000004899],
        [173.005159, 1.4863784, 0.00021450, 0.000000433],
        [48.123691, 1.4262677, 0.00037918, -0.000000003],
    ];

    /** @var array<int, array{0:float, 1:float, 2:float, 3:float}> */
    private const ELEMENT_INCLINATION = [
        [7.004986, 0.0018215, -0.00001809, 0.000000053],
        [3.394662, 0.0010037, -0.00000088, -0.000000007],
        [0.0, 0.0, 0.0, 0.0],
        [1.849726, -0.0006010, 0.00001276, -0.000000006],
        [1.303270, -0.0054966, 0.00000465, -0.000000004],
        [2.488878, -0.0037363, -0.00001516, 0.000000089],
        [0.773196, 0.0007744, 0.00003749, -0.000000092],
        [1.769952, -0.0093082, -0.00000708, 0.000000028],
    ];

    /** @var array<int, array{0:float, 1:float, 2:float, 3:float}> */
    private const ELEMENT_ECCENTRICITY = [
        [0.20563175, 0.000020406, -0.0000000284, -0.00000000017],
        [0.00677188, -0.000047766, 0.0000000975, 0.00000000044],
        [0.01670862, -0.000042037, -0.0000001236, 0.00000000004],
        [0.09340062, 0.000090483, -0.0000000806, -0.00000000035],
        [0.04849485, 0.000163244, -0.0000004719, -0.00000000197],
        [0.05550862, -0.000346818, -0.0000006456, 0.00000000338],
        [0.04629590, -0.000027337, 0.0000000790, 0.00000000025],
        [0.00898809, 0.000006408, -0.0000000008, -0.00000000005],
    ];

    /** @var array<int, array{0:float, 1:float, 2:float, 3:float}> */
    private const ELEMENT_SEMI_AXIS = [
        [0.387098310, 0.0, 0.0, 0.0],
        [0.723329820, 0.0, 0.0, 0.0],
        [1.000001018, 0.0, 0.0, 0.0],
        [1.523679342, 0.0, 0.0, 0.0],
        [5.202603191, 0.0000001913, 0.0, 0.0],
        [9.554909596, 0.0000021389, 0.0, 0.0],
        [19.218446062, -0.0000000372, 0.00000000098, 0.0],
        [30.110386869, -0.0000001663, 0.00000000069, 0.0],
    ];

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    public static function nodAps(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): array
    {
        $focalPoint = Catalog::hasFlag($method, Catalog::SE_NODBIT_FOPOINT);
        $baseMethod = $method % Catalog::SE_NODBIT_FOPOINT;
        $body = self::normalizeBody($body);

        if ($body === Catalog::SE_MOON) {
            return self::lunarNodAps($tjdEt, $flags, $baseMethod, $focalPoint);
        }

        if ($baseMethod === Catalog::SE_NODBIT_OSCU && PlanetPosition::isSupported($body)) {
            return self::planetaryOsculatingNodAps($tjdEt, $body, $flags, $focalPoint);
        }

        if (($baseMethod === 0 || Catalog::hasFlag($baseMethod, Catalog::SE_NODBIT_MEAN))
            && self::isMeanPlanetSupported($body)
        ) {
            return self::planetaryMeanNodAps($tjdEt, $body, $flags, $focalPoint);
        }

        if (($baseMethod === 0 || Catalog::hasFlag($baseMethod, Catalog::SE_NODBIT_MEAN))
            && $body == +Catalog::SE_PLUTO) {
            return self::planetaryOsculatingNodAps($tjdEt, $body, $flags, $focalPoint);
        }

        return self::errorResult(
            sprintf('nodes/apsides for planet %d are not implemented', $body)
        );
    }

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    public static function nodApsUt(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): array
    {
        return self::nodAps($tjdUt + DeltaT::deltatEx($tjdUt, $flags), $body, $flags, $method);
    }

    public static function nodApsResult(
        float $tjdEt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): NodesApsidesResult
    {
        return NodesApsidesResult::fromArray(self::nodAps($tjdEt, $body, $flags, $method));
    }

    public static function nodApsUtResult(
        float $tjdUt,
        int   $body,
        int   $flags = Catalog::SEFLG_DEFAULTEPH,
        int   $method = Catalog::SE_NODBIT_MEAN
    ): NodesApsidesResult
    {
        return NodesApsidesResult::fromArray(self::nodApsUt($tjdUt, $body, $flags, $method));
    }

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function lunarNodAps(float $tjdEt, int $flags, int $method, bool $focalPoint): array
    {
        if ($focalPoint) {
            return self::errorResult('second focal point for lunar apsides is not implemented');
        }

        $withNutation = !Catalog::hasFlag($flags, Catalog::SEFLG_NONUT)
            && !Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS);

        if (Catalog::hasFlag($method, Catalog::SE_NODBIT_OSCU)) {
            $ascNode = Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)
                ? TrueNode::geocentric($tjdEt)
                : TrueNode::apparent($tjdEt, $withNutation);

            $aphelion = Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)
                ? OsculatingApogee::geocentric($tjdEt)
                : OsculatingApogee::apparent($tjdEt, $withNutation);
        } else {
            $ascNode = Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)
                ? MeanNode::geocentric($tjdEt)
                : MeanNode::apparent($tjdEt, $withNutation);

            $aphelion = Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)
                ? MeanApogee::geocentric($tjdEt)
                : MeanApogee::apparent($tjdEt, $withNutation);
        }

        return self::finalizeResultVectors([
            'rc' => Catalog::normalizeEphemerisFlags($flags),
            'ascNode' => $ascNode,
            'descNode' => self::oppositePoint($ascNode),
            'perihelion' => self::oppositePoint($aphelion),
            'aphelion' => $aphelion,
            'error' => '',
        ], $tjdEt, $flags);
    }

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function planetaryMeanNodAps(float $tjdEt, int $body, int $flags, bool $focalPoint): array
    {
        $current = self::planetaryMeanPositions($tjdEt, $body, $focalPoint);
        $previous = self::planetaryMeanPositions($tjdEt - self::SPEED_INTERVAL, $body, $focalPoint);

        $current = self::centerPlanetaryMeanPositions($current, $tjdEt, $flags);
        $previous = self::centerPlanetaryMeanPositions($previous, $tjdEt - self::SPEED_INTERVAL, $flags);

        $result = [
            'rc' => Catalog::normalizeEphemerisFlags($flags),
            'ascNode' => self::withSpeed($current['ascNode'], $previous['ascNode']),
            'descNode' => self::withSpeed($current['descNode'], $previous['descNode']),
            'perihelion' => self::withSpeed($current['perihelion'], $previous['perihelion']),
            'aphelion' => self::withSpeed($current['aphelion'], $previous['aphelion']),
            'error' => '',
        ];

        if (!Catalog::hasFlag($flags, Catalog::SEFLG_NONUT) && !Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)) {
            $result['ascNode'] = EclipticNutation::apply($result['ascNode'], $tjdEt, true);
            $result['descNode'] = EclipticNutation::apply($result['descNode'], $tjdEt, true);
            $result['perihelion'] = EclipticNutation::apply($result['perihelion'], $tjdEt, true);
            $result['aphelion'] = EclipticNutation::apply($result['aphelion'], $tjdEt, true);
        }

        return self::finalizeResultVectors($result, $tjdEt, $flags);
    }

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function planetaryOsculatingNodAps(float $tjdEt, int $body, int $flags, bool $focalPoint): array
    {
        try {
            $distance = PlanetPosition::heliocentric($body, $tjdEt)[2];
            $interval = max(1e-8, self::OSCULATING_INTERVAL_FACTOR * $distance);

            $current = self::planetaryOsculatingCartesianPositions($tjdEt, $body, $focalPoint);
            $previous = self::planetaryOsculatingCartesianPositions($tjdEt - $interval, $body, $focalPoint);
            $next = self::planetaryOsculatingCartesianPositions($tjdEt + $interval, $body, $focalPoint);
        } catch (\InvalidArgumentException $exception) {
            return self::errorResult($exception->getMessage());
        }

        $result = [
            'rc' => Catalog::normalizeEphemerisFlags($flags),
            'ascNode' => self::fromOsculatingCartesianWithSpeed($current['ascNode'], $previous['ascNode'], $next['ascNode'], $interval),
            'descNode' => self::fromOsculatingCartesianWithSpeed($current['descNode'], $previous['descNode'], $next['descNode'], $interval),
            'perihelion' => self::fromOsculatingCartesianWithSpeed($current['perihelion'], $previous['perihelion'], $next['perihelion'], $interval),
            'aphelion' => self::fromOsculatingCartesianWithSpeed($current['aphelion'], $previous['aphelion'], $next['aphelion'], $interval),
            'error' => '',
        ];

        $result = self::centerOsculatingPlanetaryPositions($result, $tjdEt, $flags);

        if (!Catalog::hasFlag($flags, Catalog::SEFLG_NONUT) && !Catalog::hasFlag($flags, Catalog::SEFLG_TRUEPOS)) {
            $result['ascNode'] = EclipticNutation::apply($result['ascNode'], $tjdEt, true);
            $result['descNode'] = EclipticNutation::apply($result['descNode'], $tjdEt, true);
            $result['perihelion'] = EclipticNutation::apply($result['perihelion'], $tjdEt, true);
            $result['aphelion'] = EclipticNutation::apply($result['aphelion'], $tjdEt, true);
        }

        return self::finalizeResultVectors($result, $tjdEt, $flags);
    }

    /**
     * @return array{ascNode:array{0:float, 1:float, 2:float}, descNode:array{0:float, 1:float, 2:float}, perihelion:array{0:float, 1:float, 2:float}, aphelion:array{0:float, 1:float, 2:float}}
     */
    private static function planetaryOsculatingCartesianPositions(float $tjdEt, int $body, bool $focalPoint): array
    {
        $state = self::toCartesianVector(PlanetPosition::heliocentric($body, $tjdEt), true);
        $position = [$state[0], $state[1], $state[2]];
        $speed = [$state[3], $state[4], $state[5]];

        if (abs($speed[2]) < 1e-15) {
            $speed[2] = 1e-15;
        }

        $factor = $position[2] / $speed[2];
        $sign = $speed[2] / abs($speed[2]);

        $ascNode = [];
        $descNode = [];

        for ($i = 0; $i <= 2; $i++) {
            $ascNode[$i] = ($position[$i] - $factor * $speed[$i]) * $sign;
            $descNode[$i] = -$ascNode[$i];
        }

        $nodeRadius = sqrt(self::squareSum($ascNode));

        if ($nodeRadius == 0.0) {
            return [
                'ascNode' => $ascNode,
                'descNode' => $descNode,
                'perihelion' => [0.0, 0.0, 0.0],
                'aphelion' => [0.0, 0.0, 0.0],
            ];
        }

        $rxy = sqrt($ascNode[0] * $ascNode[0] + $ascNode[1] * $ascNode[1]);
        $cosNode = $ascNode[0] / $rxy;
        $sinNode = $ascNode[1] / $rxy;

        $normal = self::crossProduct($position, $speed);
        $normalRxy2 = $normal[0] * $normal[0] + $normal[1] * $normal[1];
        $normalLength2 = $normalRxy2 + $normal[2] * $normal[2];
        $normalLength = sqrt($normalLength2);

        if ($normalLength == 0.0) {
            return [
                'ascNode' => $ascNode,
                'descNode' => $descNode,
                'perihelion' => [0.0, 0.0, 0.0],
                'aphelion' => [0.0, 0.0, 0.0],
            ];
        }

        $sinInclination = sqrt($normalRxy2) / $normalLength;

        if ($sinInclination == 0.0) {
            return [
                'ascNode' => $ascNode,
                'descNode' => $descNode,
                'perihelion' => [0.0, 0.0, 0.0],
                'aphelion' => [0.0, 0.0, 0.0],
            ];
        }

        $cosInclination = sqrt(max(0.0, 1.0 - $sinInclination * $sinInclination));

        if ($normal[2] < 0.0) {
            $cosInclination = -$cosInclination;
        }

        $cosU = $position[0] * $cosNode + $position[1] * $sinNode;
        $sinU = $position[2] / $sinInclination;
        $argumentOfLatitude = atan2($sinU, $cosU);

        $radius = sqrt(self::squareSum($position));
        $speed2 = self::squareSum($speed);
        $gm = self::solarGravitationalConstant($body);

        $semiAxis = 1.0 / (2.0 / $radius - $speed2 / $gm);
        $parameter = $normalLength2 / $gm;
        $eccentricity = sqrt(max(0.0, 1.0 - $parameter / $semiAxis));

        if ($eccentricity == 0.0) {
            return [
                'ascNode' => $ascNode,
                'descNode' => $descNode,
                'perihelion' => [0.0, 0.0, 0.0],
                'aphelion' => [0.0, 0.0, 0.0],
            ];
        }

        $cosE = (1.0 - $radius / $semiAxis) / $eccentricity;
        $sinE = self::dotProduct($position, $speed)
            / ($eccentricity * sqrt($semiAxis * $gm));

        $trueAnomaly = 2.0 * atan(
                sqrt((1.0 + $eccentricity) / (1.0 - $eccentricity))
                * $sinE
                / (1.0 + $cosE)
            );

        $perihelion = [
            self::mod2pi($argumentOfLatitude - $trueAnomaly),
            0.0,
            $semiAxis * (1.0 - $eccentricity),
        ];

        $perihelionCartesian = Coordinates::polcart($perihelion);
        $perihelionCartesian = self::coortrfSinCos($perihelionCartesian, -$sinInclination, $cosInclination);
        $perihelionPolar = Coordinates::cartpol($perihelionCartesian);
        $perihelionLongitude = self::mod2pi($perihelionPolar[0] + atan2($sinNode, $cosNode));

        $perihelionCartesian = Coordinates::polcart([
            $perihelionLongitude,
            $perihelionPolar[1],
            $perihelionPolar[2],
        ]);

        $aphelionCartesian = Coordinates::polcart([
            self::mod2pi($perihelionLongitude + M_PI),
            -$perihelionPolar[1],
            $focalPoint
                ? $semiAxis * $eccentricity * 2.0
                : $semiAxis * (1.0 + $eccentricity),
        ]);

        $nodeTrueAnomaly = self::mod2pi($trueAnomaly - $argumentOfLatitude);
        $descendingNodeTrueAnomaly = self::mod2pi($nodeTrueAnomaly + M_PI);
        $eccentricityFactor = sqrt((1.0 + $eccentricity) / (1.0 - $eccentricity));

        $nodeCosE = cos(2.0 * atan(tan($nodeTrueAnomaly / 2.0) / $eccentricityFactor));
        $descNodeCosE = cos(2.0 * atan(tan($descendingNodeTrueAnomaly / 2.0) / $eccentricityFactor));

        $nodeDistance = $semiAxis * (1.0 - $eccentricity * $nodeCosE);
        $descNodeDistance = $semiAxis * (1.0 - $eccentricity * $descNodeCosE);

        $ascOldDistance = sqrt(self::squareSum($ascNode));
        $descOldDistance = sqrt(self::squareSum($descNode));

        for ($i = 0; $i <= 2; $i++) {
            $ascNode[$i] *= $nodeDistance / $ascOldDistance;
            $descNode[$i] *= $descNodeDistance / $descOldDistance;
        }

        return [
            'ascNode' => $ascNode,
            'descNode' => $descNode,
            'perihelion' => $perihelionCartesian,
            'aphelion' => $aphelionCartesian,
        ];
    }


    /**
     * @return array{ascNode:array{0:float, 1:float, 2:float}, descNode:array{0:float, 1:float, 2:float}, perihelion:array{0:float, 1:float, 2:float}, aphelion:array{0:float, 1:float, 2:float}}
     */
    private static function planetaryMeanPositions(float $tjdEt, int $body, bool $focalPoint): array
    {
        $index = self::PLANET_ELEMENT_INDEX[$body];
        $t = ($tjdEt - Moshier::J2000) / 36525.0;

        $node = self::polynomial(self::ELEMENT_NODE[$index], $t);
        $perihelion = self::polynomial(self::ELEMENT_PERIHELION[$index], $t);
        $inclination = self::polynomial(self::ELEMENT_INCLINATION[$index], $t);
        $eccentricity = self::polynomial(self::ELEMENT_ECCENTRICITY[$index], $t);
        $semiAxis = self::polynomial(self::ELEMENT_SEMI_AXIS[$index], $t);

        $perihelionArgument = Angle::degnorm($perihelion - $node);
        $perihelionEcliptic = Coordinates::cotrans([$perihelionArgument, 0.0, 1.0], -$inclination);

        $perihelionPoint = [
            Angle::degnorm($perihelionEcliptic[0] + $node),
            $perihelionEcliptic[1],
            $semiAxis * (1.0 - $eccentricity),
        ];

        return [
            'ascNode' => [
                Angle::degnorm($node),
                0.0,
                self::orbitalRadiusAtArgument(-$perihelionArgument, $semiAxis, $eccentricity),
            ],
            'descNode' => [
                Angle::degnorm($node + 180.0),
                0.0,
                self::orbitalRadiusAtArgument(180.0 - $perihelionArgument, $semiAxis, $eccentricity),
            ],
            'perihelion' => $perihelionPoint,
            'aphelion' => [
                Angle::degnorm($perihelionPoint[0] + 180.0),
                -$perihelionPoint[1],
                $focalPoint
                    ? 2.0 * $semiAxis * $eccentricity
                    : $semiAxis * (1.0 + $eccentricity),
            ],
        ];
    }

    /**
     * @param array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string} $result
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function finalizeResultVectors(array $result, float $tjdEt, int $flags): array
    {
        $withSpeed = Catalog::wantsSpeed($flags);

        $result['ascNode'] = self::finalizeVector($result['ascNode'], $tjdEt, $flags, $withSpeed);
        $result['descNode'] = self::finalizeVector($result['descNode'], $tjdEt, $flags, $withSpeed);
        $result['perihelion'] = self::finalizeVector($result['perihelion'], $tjdEt, $flags, $withSpeed);
        $result['aphelion'] = self::finalizeVector($result['aphelion'], $tjdEt, $flags, $withSpeed);

        return $result;
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function finalizeVector(array $position, float $tjdEt, int $flags, bool $withSpeed): array
    {
        if (Catalog::hasFlag($flags, Catalog::SEFLG_J2000)) {
            $position = self::toJ2000($position, $tjdEt);
        }

        if (!$withSpeed) {
            $position[3] = 0.0;
            $position[4] = 0.0;
            $position[5] = 0.0;
        }

        if (Catalog::hasFlag($flags, Catalog::SEFLG_EQUATORIAL)) {
            $position = self::toEquatorial($position, $tjdEt, $flags);
        }

        if (Catalog::hasFlag($flags, Catalog::SEFLG_XYZ)) {
            return self::toCartesianVector($position, $withSpeed);
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
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function toEquatorial(array $position, float $tjdEt, int $flags): array
    {
        $eps = SiderealTime::meanObliquity($tjdEt);

        if (!Catalog::hasFlag($flags, Catalog::SEFLG_NONUT)) {
            $nutation = SiderealTime::nutationApprox($tjdEt);
            $eps += $nutation['deps'];
        }

        return Coordinates::cotransSp($position, -$eps);
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function toCartesianVector(array $position, bool $withSpeed): array
    {
        $position[0] = deg2rad($position[0]);
        $position[1] = deg2rad($position[1]);
        $position[3] = $withSpeed ? deg2rad($position[3]) : 0.0;
        $position[4] = $withSpeed ? deg2rad($position[4]) : 0.0;
        $position[5] = $withSpeed ? $position[5] : 0.0;

        return Coordinates::polcartSp($position);
    }

    /**
     * @param array<int, float> $position
     * @return array<int, float>
     */
    private static function toJ2000(array $position, float $tjdEt): array
    {
        $polar = $position;
        $polar[0] = deg2rad($polar[0]);
        $polar[1] = deg2rad($polar[1]);
        $polar[3] = deg2rad($polar[3]);
        $polar[4] = deg2rad($polar[4]);

        $cartesian = Coordinates::polcartSp($polar);

        $precessedPosition = Precession::precess(
            [$cartesian[0], $cartesian[1], $cartesian[2]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $precessedSpeed = Precession::precess(
            [$cartesian[3], $cartesian[4], $cartesian[5]],
            $tjdEt,
            Precession::DIRECTION_TO_J2000,
            Precession::MODEL_IAU_1976
        );

        $result = Coordinates::cartpolSp([
            $precessedPosition[0],
            $precessedPosition[1],
            $precessedPosition[2],
            $precessedSpeed[0],
            $precessedSpeed[1],
            $precessedSpeed[2],
        ]);

        return [
            rad2deg($result[0]),
            rad2deg($result[1]),
            $result[2],
            rad2deg($result[3]),
            rad2deg($result[4]),
            $result[5],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $current
     * @param array{0:float, 1:float, 2:float} $previous
     * @param array{0:float, 1:float, 2:float} $next
     * @return array<int, float>
     */
    private static function fromOsculatingCartesianWithSpeed(array $current, array $previous, array $next, float $interval): array
    {
        return self::fromCartesianVector([
            $current[0],
            $current[1],
            $current[2],
            ($next[0] - $previous[0]) / (2.0 * $interval),
            ($next[1] - $previous[1]) / (2.0 * $interval),
            ($next[2] - $previous[2]) / (2.0 * $interval),
        ]);
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $position
     * @return array<int, float>
     */
    private static function fromCartesianVector(array $position): array
    {
        $polar = Coordinates::cartpolSp($position);

        return [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
            rad2deg($polar[3]),
            rad2deg($polar[4]),
            $polar[5],
        ];
    }

    /**
     * @param array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string} $result
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function centerOsculatingPlanetaryPositions(array $result, float $tjdEt, int $flags): array
    {
        if (Catalog::hasFlag($flags, Catalog::SEFLG_HELCTR) || Catalog::hasFlag($flags, Catalog::SEFLG_BARYCTR)) {
            return $result;
        }

        $earth = self::toCartesianVector(EarthPosition::heliocentric($tjdEt), true);

        $result['ascNode'] = self::fromCartesianVector(
            self::subtract6(self::toCartesianVector($result['ascNode'], true), $earth)
        );
        $result['descNode'] = self::fromCartesianVector(
            self::subtract6(self::toCartesianVector($result['descNode'], true), $earth)
        );
        $result['perihelion'] = self::fromCartesianVector(
            self::subtract6(self::toCartesianVector($result['perihelion'], true), $earth)
        );
        $result['aphelion'] = self::fromCartesianVector(
            self::subtract6(self::toCartesianVector($result['aphelion'], true), $earth)
        );

        return $result;
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $left
     * @param array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float} $right
     * @return array{0:float, 1:float, 2:float, 3:float, 4:float, 5:float}
     */
    private static function subtract6(array $left, array $right): array
    {
        return [
            $left[0] - $right[0],
            $left[1] - $right[1],
            $left[2] - $right[2],
            $left[3] - $right[3],
            $left[4] - $right[4],
            $left[5] - $right[5],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     * @return array{0:float, 1:float, 2:float}
     */
    private static function crossProduct(array $left, array $right): array
    {
        return [
            $left[1] * $right[2] - $left[2] * $right[1],
            $left[2] * $right[0] - $left[0] * $right[2],
            $left[0] * $right[1] - $left[1] * $right[0],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     */
    private static function dotProduct(array $left, array $right): float
    {
        return $left[0] * $right[0] + $left[1] * $right[1] + $left[2] * $right[2];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $vector
     */
    private static function squareSum(array $vector): float
    {
        return $vector[0] * $vector[0] + $vector[1] * $vector[1] + $vector[2] * $vector[2];
    }

    private static function mod2pi(float $value): float
    {
        $value = fmod($value, 2.0 * M_PI);

        if ($value < 0.0) {
            $value += 2.0 * M_PI;
        }

        return $value;
    }

    /**
     * @param array{0:float, 1:float, 2:float} $position
     * @return array{0:float, 1:float, 2:float}
     */
    private static function coortrfSinCos(array $position, float $sinEps, float $cosEps): array
    {
        return [
            $position[0],
            $position[1] * $cosEps + $position[2] * $sinEps,
            -$position[1] * $sinEps + $position[2] * $cosEps,
        ];
    }

    private static function solarGravitationalConstant(int $body): float
    {
        $massRatio = self::PLANET_MASS_RATIO[$body] ?? INF;
        $planetMass = is_infinite($massRatio) ? 0.0 : 1.0 / $massRatio;

        return self::HELGRAVCONST
            * (1.0 + $planetMass)
            / self::AUNIT
            / self::AUNIT
            / self::AUNIT
            * 86400.0
            * 86400.0;
    }

    /**
     * @param array{
     *     ascNode:array{0:float, 1:float, 2:float},
     *     descNode:array{0:float, 1:float, 2:float},
     *     perihelion:array{0:float, 1:float, 2:float},
     *     aphelion:array{0:float, 1:float, 2:float}
     * } $positions
     * @return array{
     *     ascNode:array{0:float, 1:float, 2:float},
     *     descNode:array{0:float, 1:float, 2:float},
     *     perihelion:array{0:float, 1:float, 2:float},
     *     aphelion:array{0:float, 1:float, 2:float}
     *  }
     */
    private static function centerPlanetaryMeanPositions(array $positions, float $tjdEt, int $flags): array
    {
        if (Catalog::hasFlag($flags, Catalog::SEFLG_HELCTR) || Catalog::hasFlag($flags, Catalog::SEFLG_BARYCTR)) {
            return $positions;
        }

        $earth = self::toCartesian3(EarthPosition::heliocentric($tjdEt));

        return [
            'ascNode' => self::fromCartesian3(self::subtract3(self::toCartesian3($positions['ascNode']), $earth)),
            'descNode' => self::fromCartesian3(self::subtract3(self::toCartesian3($positions['descNode']), $earth)),
            'perihelion' => self::fromCartesian3(self::subtract3(self::toCartesian3($positions['perihelion']), $earth)),
            'aphelion' => self::fromCartesian3(self::subtract3(self::toCartesian3($positions['aphelion']), $earth)),
        ];
    }

    /**
     * @param array<int, float> $position
     * @return array{0:float, 1:float, 2:float}
     */
    private static function toCartesian3(array $position): array
    {
        return Coordinates::polcart([
            deg2rad($position[0]),
            deg2rad($position[1]),
            $position[2],
        ]);
    }

    /**
     * @param array{0:float, 1:float, 2:float} $position
     * @return array{0:float, 1:float, 2:float}
     */
    private static function fromCartesian3(array $position): array
    {
        $polar = Coordinates::cartpol($position);

        return [
            Angle::degnorm(rad2deg($polar[0])),
            rad2deg($polar[1]),
            $polar[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float} $left
     * @param array{0:float, 1:float, 2:float} $right
     * @return array{0:float, 1:float, 2:float}
     */
    private static function subtract3(array $left, array $right): array
    {
        return [
            $left[0] - $right[0],
            $left[1] - $right[1],
            $left[2] - $right[2],
        ];
    }

    /**
     * @param array{0:float, 1:float, 2:float, 3:float} $coefficients
     */
    private static function polynomial(array $coefficients, float $t): float
    {
        return $coefficients[0]
            + $coefficients[1] * $t
            + $coefficients[2] * $t * $t
            + $coefficients[3] * $t * $t * $t;
    }

    private static function orbitalRadiusAtArgument(float $trueAnomaly, float $semiAxis, float $eccentricity): float
    {
        $angle = deg2rad($trueAnomaly);
        $cosAngle = cos($angle);

        if (abs($cosAngle) < 1e-15) {
            return $semiAxis * (1.0 - $eccentricity * $eccentricity)
                / (1.0 + $eccentricity * $cosAngle);
        }

        $eccentricAnomaly = 2.0 * atan(
                tan($angle / 2.0) * sqrt((1.0 - $eccentricity) / (1.0 + $eccentricity))
            );

        return $semiAxis * (cos($eccentricAnomaly) - $eccentricity) / $cosAngle;
    }

    /**
     * @param array{0:float, 1:float, 2:float} $current
     * @param array{0:float, 1:float, 2:float} $previous
     * @return array<int, float>
     */
    private static function withSpeed(array $current, array $previous): array
    {
        return [
            $current[0],
            $current[1],
            $current[2],
            Angle::difdeg2n($current[0], $previous[0]) / self::SPEED_INTERVAL,
            ($current[1] - $previous[1]) / self::SPEED_INTERVAL,
            ($current[2] - $previous[2]) / self::SPEED_INTERVAL,
        ];
    }

    private static function normalizeBody(int $body): int
    {
        if ($body === Catalog::SE_AST_OFFSET + 134340) {
            return Catalog::SE_PLUTO;
        }

        return $body;
    }

    private static function isMeanPlanetSupported(int $body): bool
    {
        return array_key_exists($body, self::PLANET_ELEMENT_INDEX);
    }

    /**
     * @param array<int, float> $point
     * @return array<int, float>
     */
    private static function oppositePoint(array $point): array
    {
        return [
            Angle::degnorm($point[0] + 180.0),
            -$point[1],
            $point[2],
            $point[3],
            -$point[4],
            $point[5],
        ];
    }

    /**
     * @return array{rc:int, ascNode:array<int, float>, descNode:array<int, float>, perihelion:array<int, float>, aphelion:array<int, float>, error:string}
     */
    private static function errorResult(string $error): array
    {
        return [
            'rc' => SwissDate::ERR,
            'ascNode' => self::zeroVector(),
            'descNode' => self::zeroVector(),
            'perihelion' => self::zeroVector(),
            'aphelion' => self::zeroVector(),
            'error' => $error,
        ];
    }

    /**
     * @return array<int, float>
     */
    private static function zeroVector(): array
    {
        return [0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    }
}
