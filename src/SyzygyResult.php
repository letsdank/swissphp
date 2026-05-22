<?php

declare(strict_types=1);

namespace SwissEph;

final class SyzygyResult
{
    /**
     * @param array<int, float> $attributes
     */
    public function __construct(
        public readonly int    $rc,
        public readonly float  $tjd,
        public readonly float  $targetPhase,
        public readonly float  $phaseAngle,
        public readonly float  $sunLongitude,
        public readonly float  $moonLongitude,
        public readonly string $error,
        public readonly array  $attributes = [],
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'tjd' => $this->tjd,
            'targetPhase' => $this->targetPhase,
            'phaseAngle' => $this->phaseAngle,
            'sunLongitude' => $this->sunLongitude,
            'moonLongitude' => $this->moonLongitude,
            'error' => $this->error,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * @param array{rc:int, tjd:float, targetPhase:float, phaseAngle:float, sunLongitude:float, moonLongitude:float, error:string, attributes?:array<int, float>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['rc'],
            $data['tjd'],
            $data['targetPhase'],
            $data['phaseAngle'],
            $data['sunLongitude'],
            $data['moonLongitude'],
            $data['error'],
            $data['attributes'] ?? [],
        );
    }
}