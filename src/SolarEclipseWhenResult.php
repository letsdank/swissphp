<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class SolarEclipseWhenResult
{
    public function __construct(
        public EclipseWhenResult $result
    )
    {
    }

    /**
     * @param array{rc:int, tret:array<int, float>, attr:array<int, float>, dcore?:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(EclipseWhenResult::fromArray($result));
    }

    /**
     * @return array{rc:int, tret:array<int, float>, attr:array<int, float>, dcore?:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return $this->result->toArray();
    }

    public function isEclipse(): bool
    {
        return $this->result->isEclipse();
    }

    public function isVisible(): bool
    {
        return $this->result->isVisible();
    }

    public function isMaximumVisible(): bool
    {
        return $this->result->isMaximumVisible();
    }

    public function isTotal(): bool
    {
        return $this->result->isTotal();
    }

    public function isAnnular(): bool
    {
        return ($this->result->rc & Catalog::SE_ECL_ANNULAR) !== 0;
    }

    public function isPartial(): bool
    {
        return $this->result->isPartial();
    }

    public function maximumTime(): float
    {
        return $this->result->maximumTime();
    }

    public function firstContactTime(): float
    {
        return $this->result->firstContactTime();
    }

    public function secondContactTime(): float
    {
        return $this->result->secondContactTime();
    }

    public function thirdContactTime(): float
    {
        return $this->result->thirdContactTime();
    }

    public function fourthContactTime(): float
    {
        return $this->result->fourthContactTime();
    }

    public function partialBeginTime(): float
    {
        return $this->result->partialBeginTime();
    }

    public function partialEndTime(): float
    {
        return $this->result->partialEndTime();
    }

    public function totalityBeginTime(): float
    {
        return $this->result->totalityBeginTime();
    }

    public function totalityEndTime(): float
    {
        return $this->result->totalityEndTime();
    }

    public function centralLineBeginTime(): float
    {
        return $this->result->penumbralBeginTime();
    }

    public function centralLineEndTime(): float
    {
        return $this->result->penumbralEndTime();
    }

    public function sunriseTime(): float
    {
        return $this->result->sunriseTime();
    }

    public function sunsetTime(): float
    {
        return $this->result->sunsetTime();
    }

    public function magnitude(): float
    {
        return $this->result->solarMagnitude();
    }

    public function obscuration(): float
    {
        return $this->result->obscuration();
    }
}