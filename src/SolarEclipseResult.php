<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class SolarEclipseResult
{
    public function __construct(
        public EclipseResult $result
    )
    {
    }

    /**
     * @param array{rc:int, attr:array<int, float>, dcore?:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(EclipseResult::fromArray($result));
    }

    /**
     * @return array{rc:int, attr:array<int, float>, dcore?:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return $this->result->toArray();
    }

    public function isEclipse(): bool
    {
        return $this->result->isEclipse();
    }

    public function isTotal(): bool
    {
        return $this->result->isTotal();
    }

    public function isAnnular(): bool
    {
        return ($this->result->rc & Catalog::SE_ECL_ANNULAR) !== 0;
    }

    public function isPartial(): bool
    {
        return $this->result->isPartial();
    }

    public function magnitude(): float
    {
        return $this->result->solarMagnitude();
    }

    public function lunarSolarDiameterRatio(): float
    {
        return $this->result->lunarSolarDiameterRatio();
    }

    public function obscuration(): float
    {
        return $this->result->obscuration();
    }

    public function coreShadowDiameterKm(): float
    {
        return $this->result->coreShadowDiameterKm();
    }

    public function sunAzimuth(): float
    {
        return $this->result->sunAzimuth();
    }

    public function sunTrueAltitude(): float
    {
        return $this->result->sunTrueAltitude();
    }

    public function sunApparentAltitude(): float
    {
        return $this->result->sunApparentAltitude();
    }

    public function elongation(): float
    {
        return $this->result->solarElongation();
    }

    public function nasaMagnitude(): float
    {
        return $this->result->nasaMagnitude();
    }

    public function sarosSeries(): int
    {
        return $this->result->sarosSeries();
    }

    public function sarosMember(): int
    {
        return $this->result->sarosMember();
    }
}