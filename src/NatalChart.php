<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final readonly class NatalChart
{
    /**
     * @param array<string, NatalChartPoint> $points
     * @param array<int, NatalChartHouse> $houses
     * @param array<int, NatalChartAspect> $aspects
     */
    public function __construct(
        public float  $tjdUt,
        public float  $geoLat,
        public float  $geoLon,
        public string $houseSystem,
        public array  $points,
        public array  $houses = [],
        public array  $aspects = [],
    )
    {
    }

    public function point(string $name): NatalChartPoint
    {
        if (!isset($this->points[$name])) {
            throw new InvalidArgumentException('Natal chart point is not available.');
        }

        return $this->points[$name];
    }

    public function hasPoint(string $name): bool
    {
        return isset($this->points[$name]);
    }

    public function house(int $number): NatalChartHouse
    {
        if (!isset($this->houses[$number])) {
            throw new InvalidArgumentException('Natal chart house is not available.');
        }

        return $this->houses[$number];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tjdUt' => $this->tjdUt,
            'geoLat' => $this->geoLat,
            'geoLon' => $this->geoLon,
            'houseSystem' => $this->houseSystem,
            'points' => array_map(
                static fn(NatalChartPoint $point): array => $point->toArray(),
                $this->points
            ),
            'houses' => array_map(
                static fn(NatalChartHouse $house): array => $house->toArray(),
                $this->houses
            ),
            'aspects' => array_map(
                static fn(NatalChartAspect $aspect): array => $aspect->toArray(),
                $this->aspects
            ),
        ];
    }
}