<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class AspectSet
{
    /**
     * @param array<float|int, string> $aspects
     */
    public function __construct(
        public array $aspects,
        public float $maxOrb
    )
    {
    }

    public static function major(float $maxOrb): self
    {
        return new self([
            Aspect::CONJUNCTION => 'conjunction',
            Aspect::SEXTILE => 'sextile',
            Aspect::SQUARE => 'square',
            Aspect::TRINE => 'trine',
            Aspect::OPPOSITION => 'opposition',
        ], $maxOrb);
    }

    public static function majorAndMinor(float $maxOrb): self
    {
        return new self([
            Aspect::CONJUNCTION => 'conjunction',
            Aspect::SEMISEXTILE => 'semisextile',
            Aspect::SEMISQUARE => 'semisquare',
            Aspect::SEXTILE => 'sextile',
            Aspect::SQUARE => 'square',
            Aspect::TRINE => 'trine',
            Aspect::SESQUIQUADRATE => 'sesquiquadrate',
            Aspect::QUINCUNX => 'quincunx',
            Aspect::OPPOSITION => 'opposition',
        ], $maxOrb);
    }

    public function nearest(float $firstLongitude, float $secondLongitude): Aspect
    {
        return Aspect::nearestFromList($firstLongitude, $secondLongitude, $this->aspects);
    }

    public function match(float $firstLongitude, float $secondLongitude): ?Aspect
    {
        $aspect = $this->nearest($firstLongitude, $secondLongitude);

        return $aspect->orb <= $this->maxOrb ? $aspect : null;
    }
}