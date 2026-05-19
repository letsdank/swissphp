<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class CrossingResult
{
    public function __construct(
        public int    $rc,
        public float  $tjd,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, tjd:float, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['tjd'], $result['error']);
    }

    /**
     * @return array{rc:int, tjd:float, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'tjd' => $this->tjd,
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
}