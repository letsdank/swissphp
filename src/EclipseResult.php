<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class EclipseResult
{
    /**
     * @param array<int, float> $attributes
     * @param array<int, float> $core
     */
    public function __construct(
        public int    $rc,
        public array  $attributes,
        public array  $core = [],
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, attr:array<int, float>, dcore?:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            $result['rc'],
            $result['attr'],
            $result['dcore'] ?? [],
            $result['error']
        );
    }

    /**
     * @return array{rc:int, attr:array<int, float>, dcore:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'attr' => $this->attributes,
            'dcore' => $this->core,
            'error' => $this->error,
        ];
    }

    public function isEclipse(): bool
    {
        return $this->rc > 0;
    }

    public function isTotal(): bool
    {
        return ($this->rc & Catalog::SE_ECL_TOTAL) !== 0;
    }

    public function isPartial(): bool
    {
        return ($this->rc & Catalog::SE_ECL_PARTIAL) !== 0;
    }

    public function isPenumbral(): bool
    {
        return ($this->rc & Catalog::SE_ECL_PENUMBRAL) !== 0;
    }

    public function umbralMagnitude(): float
    {
        return $this->attributes[0] ?? 0.0;
    }

    public function penumbralMagnitude(): float
    {
        return $this->attributes[1] ?? 0.0;
    }

    public function solarMagnitude(): float
    {
        return $this->attributes[0] ?? 0.0;
    }

    public function lunarSolarDiameterRatio(): float
    {
        return $this->attributes[1] ?? 0.0;
    }

    public function obscuration(): float
    {
        return $this->attributes[2] ?? 0.0;
    }

    public function coreShadowDiameterKm(): float
    {
        return $this->attributes[3] ?? 0.0;
    }

    public function moonAzimuth(): float
    {
        return $this->attributes[4] ?? 0.0;
    }

    public function moonTrueAltitude(): float
    {
        return $this->attributes[5] ?? 0.0;
    }

    public function moonApparentAltitude(): float
    {
        return $this->attributes[6] ?? 0.0;
    }

    public function sunAzimuth(): float
    {
        return $this->attributes[4] ?? 0.0;
    }

    public function sunTrueAltitude(): float
    {
        return $this->attributes[5] ?? 0.0;
    }

    public function sunApparentAltitude(): float
    {
        return $this->attributes[6] ?? 0.0;
    }

    public function distanceFromOpposition(): float
    {
        return $this->attributes[7] ?? 0.0;
    }

    public function solarElongation(): float
    {
        return $this->attributes[7] ?? 0.0;
    }

    public function nasaMagnitude(): float
    {
        return $this->attributes[8] ?? 0.0;
    }

    public function sarosSeries(): int
    {
        return (int)($this->attributes[9] ?? 0.0);
    }

    public function sarosMember(): int
    {
        return (int)($this->attributes[10] ?? 0.0);
    }
}