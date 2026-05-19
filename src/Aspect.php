<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class Aspect
{
    public const CONJUNCTION = 0.0;
    public const SEXTILE = 60.0;
    public const SQUARE = 90.0;
    public const TRINE = 120.0;
    public const OPPOSITION = 180.0;

    public const SEMISEXTILE = 30.0;
    public const SEMISQUARE = 45.0;
    public const SESQUIQUADRATE = 135.0;
    public const QUINCUNX = 150.0;

    private const MAJOR_ASPECTS = [
        self::CONJUNCTION => 'conjunction',
        self::SEXTILE => 'sextile',
        self::SQUARE => 'square',
        self::TRINE => 'trine',
        self::OPPOSITION => 'opposition',
    ];

    private const MINOR_ASPECTS = [
        self::SEMISEXTILE => 'semisextile',
        self::SEMISQUARE => 'semisquare',
        self::SESQUIQUADRATE => 'sesquiquadrate',
        self::QUINCUNX => 'quincunx',
    ];

    public function __construct(
        public float  $angle,
        public string $name,
        public float  $orb
    )
    {
    }

    public static function angularDistance(float $firstLongitude, float $secondLongitude): float
    {
        return abs(Angle::difdeg2n($firstLongitude, $secondLongitude));
    }

    public static function nearestMajor(float $firstLongitude, float $secondLongitude): self
    {
        $distance = self::angularDistance($firstLongitude, $secondLongitude);

        $nearestAngle = self::CONJUNCTION;
        $nearestOrb = abs($distance - self::CONJUNCTION);

        foreach (self::MAJOR_ASPECTS as $angle => $name) {
            $orb = abs($distance - (float)$angle);

            if ($orb < $nearestOrb) {
                $nearestAngle = (float)$angle;
                $nearestOrb = $orb;
            }
        }

        return new self($nearestAngle, self::MAJOR_ASPECTS[$nearestAngle], $nearestOrb);
    }

    /**
     * @param array<float|int, string> $aspects
     */
    public static function nearestFromList(
        float $firstLongitude,
        float $secondLongitude,
        array $aspects
    ): self
    {
        $distance = self::angularDistance($firstLongitude, $secondLongitude);

        $nearestAngle = null;
        $nearestName = '';
        $nearestOrb = INF;

        foreach ($aspects as $angle => $name) {
            $angle = (float)$angle;
            $orb = abs($distance - $angle);

            if ($orb < $nearestOrb) {
                $nearestAngle = $angle;
                $nearestName = $name;
                $nearestOrb = $orb;
            }
        }

        return new self((float)$nearestAngle, $nearestName, $nearestOrb);
    }

    public static function nearestMajorOrMinor(float $firstLongitude, float $secondLongitude): self
    {
        return self::nearestFromList(
            $firstLongitude,
            $secondLongitude,
            self::MAJOR_ASPECTS + self::MINOR_ASPECTS
        );
    }

    public static function majorOrMinorWithinOrb(
        float $firstLongitude,
        float $secondLongitude,
        float $maxOrb
    ): ?self
    {
        $aspect = self::nearestMajorOrMinor($firstLongitude, $secondLongitude);

        return $aspect->orb <= $maxOrb ? $aspect : null;
    }

    public static function majorWithinOrb(
        float $firstLongitude,
        float $secondLongitude,
        float $maxOrb
    ): ?self
    {
        $aspect = self::nearestMajor($firstLongitude, $secondLongitude);

        return $aspect->orb <= $maxOrb ? $aspect : null;
    }

    public static function isWithinOrb(
        float $firstLongitude,
        float $secondLongitude,
        float $aspectAngle,
        float $maxOrb
    ): bool
    {
        return abs(self::angularDistance($firstLongitude, $secondLongitude) - $aspectAngle) <= $maxOrb;
    }

    public static function isApplying(
        float $firstLongitude,
        float $firstSpeed,
        float $secondLongitude,
        float $secondSpeed,
        float $aspectAngle
    ): bool
    {
        $currentOrb = abs(self::angularDistance($firstLongitude, $secondLongitude) - $aspectAngle);

        $nextFirstLongitude = Angle::degnorm($firstLongitude + $firstSpeed);
        $nextSecondLongitude = Angle::degnorm($secondLongitude + $secondSpeed);
        $nextOrb = abs(self::angularDistance($nextFirstLongitude, $nextSecondLongitude) - $aspectAngle);

        return $nextOrb < $currentOrb;
    }

    public function orbDms(): string
    {
        return Angle::formatDms($this->orb);
    }
}