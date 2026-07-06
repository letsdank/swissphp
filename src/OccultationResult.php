<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class OccultationResult
{
    /**
     * @param array<int, float> $geopos
     */
    public function __construct(
        public EclipseResult $result,
        public array         $geopos = [],
    )
    {
    }

    /**
     * @param array{rc:int, attr:array<int, float>, dcore?:array<int, float>, geopos?:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(EclipseResult::fromArray($result), $result['geopos'] ?? []);
    }

    /**
     * @return array{rc:int, attr:array<int, float>, dcore?:array<int, float>, geopos?:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        $array = ['rc' => $this->result->rc];

        if ($this->geopos !== []) {
            $array['geopos'] = $this->geopos;
        }

        $array['attr'] = $this->result->attributes;
        $array['dcore'] = $this->result->core;
        $array['error'] = $this->result->error;

        return $array;
    }

    public function isOccultation(): bool
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

    public function geographicLongitude(): float
    {
        return $this->geopos[0] ?? 0.0;
    }

    public function geographicLatitude(): float
    {
        return $this->geopos[1] ?? 0.0;
    }

    public function magnitude(): float
    {
        return $this->result->solarMagnitude();
    }

    public function occultedBodyMoonDiameterRatio(): float
    {
        return $this->result->lunarSolarDiameterRatio();
    }

    public function obscuration(): float
    {
        return $this->result->obscuration();
    }
}