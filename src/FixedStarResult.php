<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class FixedStarResult
{
    /**
     * @param array<int, float> $xx
     */
    public function __construct(
        public int    $rc,
        public array  $xx,
        public string $star,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, xx:array<int, float>, star:string, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['xx'], $result['star'], $result['error']);
    }

    /**
     * @return array{rc:int, xx:array<int, float>, star:string, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'xx' => $this->xx,
            'star' => $this->star,
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

    public function longitude(): float
    {
        return $this->xx[0];
    }

    public function latitude(): float
    {
        return $this->xx[1];
    }

    public function distance(): float
    {
        return $this->xx[2];
    }

    public function longitudeSpeed(): float
    {
        return $this->xx[3] ?? 0.0;
    }

    public function latitudeSpeed(): float
    {
        return $this->xx[4] ?? 0.0;
    }

    public function distanceSpeed(): float
    {
        return $this->xx[5] ?? 0.0;
    }

    public function longitudeDms(): string
    {
        return Angle::formatDms($this->longitude());
    }

    public function latitudeDms(): string
    {
        return Angle::formatDms($this->latitude());
    }

    public function longitudeZodiac(): string
    {
        return Angle::formatZodiac($this->longitude());
    }

    public function rightAscensionHms(): string
    {
        return Angle::formatHms($this->longitude());
    }
}