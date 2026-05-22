<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class NatalChartHouse
{
    public function __construct(
        public int   $number,
        public float $cusp,
    )
    {
    }

    public function normalizedCusp(): float
    {
        return Angle::degnorm($this->cusp);
    }

    public function signIndex(): int
    {
        return intdiv((int)floor($this->normalizedCusp()), 30);
    }

    public function signDegree(): float
    {
        return $this->normalizedCusp() - $this->signIndex() * 30.0;
    }

    /**
     * @return array{number:int, cusp:float, signIndex:int, signDegree:float}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'cusp' => $this->cusp,
            'signIndex' => $this->signIndex(),
            'signDegree' => $this->signDegree(),
        ];
    }
}