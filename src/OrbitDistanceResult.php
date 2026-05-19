<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class OrbitDistanceResult
{
    public function __construct(
        public int    $rc,
        public float  $max,
        public float  $min,
        public float  $true,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, max:float, min:float, true:float, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            $result['rc'],
            $result['max'],
            $result['min'],
            $result['true'],
            $result['error'],
        );
    }

    /**
     * @return array{rc:int, max:float, min:float, true:float, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'max' => $this->max,
            'min' => $this->min,
            'true' => $this->true,
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

    public function aphelionDistance(): float
    {
        return $this->max;
    }

    public function perihelionDistance(): float
    {
        return $this->min;
    }

    public function currentDistance(): float
    {
        return $this->true;
    }

    public function relativeDistance(): float
    {
        if ($this->max == $this->min) {
            return 0.0;
        }

        return ($this->true - $this->min) / ($this->max - $this->min);
    }
}