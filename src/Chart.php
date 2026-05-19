<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final readonly class Chart
{
    /**
     * @param array<string, CalculationResult> $positions
     */
    public function __construct(
        public array         $positions,
        public ?HousesResult $houses = null
    )
    {
    }

    public function position(string $name): CalculationResult
    {
        if (!isset($this->positions[$name])) {
            throw new InvalidArgumentException('Position is not available in chart.');
        }

        return $this->positions[$name];
    }

    public function hasPosition(string $name): bool
    {
        return isset($this->positions[$name]);
    }

    /**
     * @return array<string, CalculationResult>
     */
    public function positions(): array
    {
        return $this->positions;
    }

    /**
     * @return array<int, array{first:string, second:string, aspect:AspectResult}>
     */
    public function aspects(AspectSet $aspectSet): array
    {
        return AspectMatrix::between($this->positions, $aspectSet);
    }

    /**
     * @return array<int, array{first:string, second:string, aspect:AspectResult}>
     */
    public function aspectsTo(self $other, AspectSet $aspectSet): array
    {
        return AspectMatrix::cross($this->positions, $other->positions, $aspectSet);
    }

    public function withHouses(HousesResult $houses): self
    {
        return new self($this->positions, $houses);
    }
}