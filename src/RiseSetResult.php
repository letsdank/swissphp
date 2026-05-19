<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class RiseSetResult
{
    public function __construct(
        public float $tjdUt,
        public float $azimuth,
        public float $trueAltitude,
        public float $apparentAltitude
    )
    {
    }

    /**
     * @param array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            $result['tjdUt'],
            $result['azimuth'],
            $result['trueAltitude'],
            $result['apparentAltitude'],
        );
    }

    /**
     * @return array{tjdUt:float, azimuth:float, trueAltitude:float, apparentAltitude:float}
     */
    public function toArray(): array
    {
        return [
            'tjdUt' => $this->tjdUt,
            'azimuth' => $this->azimuth,
            'trueAltitude' => $this->trueAltitude,
            'apparentAltitude' => $this->apparentAltitude,
        ];
    }

    public function apparentRefraction(): float
    {
        return $this->apparentAltitude - $this->trueAltitude;
    }

    public function azimuthDms(): string
    {
        return Angle::formatDms($this->azimuth);
    }

    public function trueAltitudeDms(): string
    {
        return Angle::formatDms($this->trueAltitude);
    }

    public function apparentAltitudeDms(): string
    {
        return Angle::formatDms($this->apparentAltitude);
    }
}