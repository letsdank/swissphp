<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class AspectResult
{
    public function __construct(
        public Aspect $aspect,
        public bool   $applying
    )
    {
    }

    public function isApplying(): bool
    {
        return $this->applying;
    }

    public function isSeparating(): bool
    {
        return !$this->applying;
    }

    public function name(): string
    {
        return $this->aspect->name;
    }

    public function angle(): float
    {
        return $this->aspect->angle;
    }

    public function orb(): float
    {
        return $this->aspect->orb;
    }

    public function orbDms(): string
    {
        return $this->aspect->orbDms();
    }
}