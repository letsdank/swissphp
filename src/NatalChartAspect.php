<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class NatalChartAspect
{
    public function __construct(
        public string $first,
        public string $second,
        public string $name,
        public float  $angle,
        public float  $orb,
        public bool   $applying,
    )
    {
    }

    public function isSeparating(): bool
    {
        return !$this->applying;
    }

    /**
     * @return array{first:string, second:string, name:string, angle:float, orb:float, applying:bool}
     */
    public function toArray(): array
    {
        return [
            'first' => $this->first,
            'second' => $this->second,
            'name' => $this->name,
            'angle' => $this->angle,
            'orb' => $this->orb,
            'applying' => $this->applying,
        ];
    }
}