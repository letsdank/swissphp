<?php

declare(strict_types=1);

namespace SwissEph;

use InvalidArgumentException;

final readonly class HousesResult
{
    /**
     * @param array<int, float> $cusps
     * @param array<int, float> $ascmc
     */
    public function __construct(
        public array $cusps,
        public array $ascmc
    )
    {
    }

    /**
     * @param array{cusps:array<int, float>, ascmc:array<int, float>} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['cusps'], $result['ascmc']);
    }

    /**
     * @return array{cusps:array<int, float>, ascmc:array<int, float>}
     */
    public function toArray(): array
    {
        return [
            'cusps' => $this->cusps,
            'ascmc' => $this->ascmc,
        ];
    }

    /**
     * @return array<int, float>
     */
    public function cusps(): array
    {
        return $this->cusps;
    }

    /**
     * @return array<int, float>
     */
    public function ascmc(): array
    {
        return $this->ascmc;
    }

    public function hasCusp(int $index): bool
    {
        return isset($this->cusps[$index]);
    }

    public function axis(int $index): float
    {
        if (!isset($this->ascmc[$index])) {
            throw new InvalidArgumentException('ASCMC index is out of range.');
        }

        return $this->ascmc[$index];
    }

    public function cusp(int $index): float
    {
        if (!isset($this->cusps[$index])) {
            throw new InvalidArgumentException('House cusp index is out of range.');
        }

        return $this->cusps[$index];
    }

    public function cuspDms(int $index): string
    {
        return Angle::formatDms($this->cusp($index));
    }

    public function cuspZodiac(int $index): string
    {
        return Angle::formatZodiac($this->cusp($index));
    }

    public function cuspSign(int $index): int
    {
        return Angle::zodiacSign($this->cusp($index));
    }

    public function cuspSignShortName(int $index): string
    {
        return Angle::zodiacSignShortName($this->cusp($index));
    }

    public function cuspSignName(int $index): string
    {
        return Angle::zodiacSignName($this->cusp($index));
    }

    public function cuspDegreeInSign(int $index): float
    {
        return Angle::degreeInSign($this->cusp($index));
    }

    public function ascendant(): float
    {
        return $this->axis(0);
    }

    public function mc(): float
    {
        return $this->axis(1);
    }

    public function armc(): float
    {
        return $this->axis(2);
    }

    public function vertex(): float
    {
        return $this->axis(3);
    }

    public function equatorialAscendant(): float
    {
        return $this->axis(4);
    }

    public function coAscendantKoch(): float
    {
        return $this->axis(5);
    }

    public function coAscendantMunkasey(): float
    {
        return $this->axis(6);
    }

    public function polarAscendant(): float
    {
        return $this->axis(7);
    }

    public function ascendantZodiac(): string
    {
        return Angle::formatZodiac($this->ascendant());
    }

    public function mcZodiac(): string
    {
        return Angle::formatZodiac($this->mc());
    }

    public function armcHms(): string
    {
        return Angle::formatHms($this->armc());
    }

    public function vertexZodiac(): string
    {
        return Angle::formatZodiac($this->vertex());
    }
}