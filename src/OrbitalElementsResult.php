<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class OrbitalElementsResult
{
    /**
     * @param array<int, float> $dret
     */
    public function __construct(
        public int    $rc,
        public array  $dret,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, dret:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['dret'], $result['error']);
    }

    /**
     * @return array{rc:int, dret:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'dret' => $this->dret,
            'error' => $this->error,
        ];
    }

    public function isOk(): bool
    {
        return $this->rc !== SwissDate::ERR;
    }

    public function hasError(): bool
    {
        return !$this->isOk() || $this->error !== '';
    }

    public function semiMajorAxis(): float
    {
        return $this->dret[0];
    }

    public function eccentricity(): float
    {
        return $this->dret[1];
    }

    public function inclination(): float
    {
        return $this->dret[2];
    }

    public function ascendingNodeLongitude(): float
    {
        return $this->dret[3];
    }

    public function argumentOfPeriapsis(): float
    {
        return $this->dret[4];
    }

    public function periapsisLongitude(): float
    {
        return $this->dret[5];
    }

    public function meanAnomaly(): float
    {
        return $this->dret[6];
    }

    public function trueAnomaly(): float
    {
        return $this->dret[7];
    }

    public function eccentricAnomaly(): float
    {
        return $this->dret[8];
    }

    public function meanLongitude(): float
    {
        return $this->dret[9];
    }

    public function siderealPeriodYears(): float
    {
        return $this->dret[10];
    }

    public function meanDailyMotion(): float
    {
        return $this->dret[11];
    }

    public function tropicalPeriodYears(): float
    {
        return $this->dret[12];
    }

    public function synodicPeriodYears(): float
    {
        return $this->dret[13];
    }

    public function perihelionPassageTime(): float
    {
        return $this->dret[14];
    }

    public function perihelionDistance(): float
    {
        return $this->dret[15];
    }

    public function aphelionDistance(): float
    {
        return $this->dret[16];
    }

    public function inclinationDms(): string
    {
        return Angle::formatDms($this->inclination());
    }

    public function ascendingNodeDms(): string
    {
        return Angle::formatDms($this->ascendingNodeLongitude());
    }

    public function periapsisDms(): string
    {
        return Angle::formatDms($this->periapsisLongitude());
    }

    public function meanLongitudeDms(): string
    {
        return Angle::formatDms($this->meanLongitude());
    }
}