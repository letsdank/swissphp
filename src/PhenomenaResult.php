<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class PhenomenaResult
{
    /**
     * @param array<int, float> $attr
     */
    public function __construct(
        public int    $rc,
        public array  $attr,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, attr:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['attr'], $result['error']);
    }

    /**
     * @return array{rc:int, attr:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'attr' => $this->attr,
            'error' => $this->error,
        ];
    }

    public function phaseAngle(): float
    {
        return $this->attr[0];
    }

    public function illuminatedFraction(): float
    {
        return $this->attr[1];
    }

    public function elongation(): float
    {
        return $this->attr[2];
    }

    public function apparentDiameter(): float
    {
        return $this->attr[3];
    }

    public function apparentMagnitude(): float
    {
        return $this->attr[4];
    }

    public function horizontalParallax(): float
    {
        return $this->attr[5];
    }

    public function isOk(): bool
    {
        return $this->rc !== SwissDate::ERR;
    }

    public function hasError(): bool
    {
        return !$this->isOk() || $this->error !== '';
    }

    public function phaseAngleDms(): string
    {
        return Angle::formatDms($this->phaseAngle());
    }

    public function elongationDms(): string
    {
        return Angle::formatDms($this->elongation());
    }

    public function apparentDiameterDms(): string
    {
        return Angle::formatDms($this->apparentDiameter());
    }

    public function horizontalParallaxDms(): string
    {
        return Angle::formatDms($this->horizontalParallax());
    }
}