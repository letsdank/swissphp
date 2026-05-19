<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class FixedStarMagnitudeResult
{
    public function __construct(
        public int    $rc,
        public float  $mag,
        public string $star,
        public string $error = '',
    )
    {
    }

    /**
     * @param array{rc:int, mag:float, star:string, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['mag'], $result['star'], $result['error']);
    }

    /**
     * @return array{rc:int, mag:float, star:string, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'mag' => $this->mag,
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
}