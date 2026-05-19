<?php

declare(strict_types=1);

namespace SwissEph;

final readonly class CalculationResult
{
    /**
     * @param array<int, float> $xx
     */
    public function __construct(
        public int    $rc,
        public array  $xx,
        public string $error = ''
    )
    {
    }

    /**
     * @param array{rc:int, xx:array<int, float>, error:string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self($result['rc'], $result['xx'], $result['error']);
    }

    /**
     * @return array{rc:int, xx:array<int, float>, error:string}
     */
    public function toArray(): array
    {
        return [
            'rc' => $this->rc,
            'xx' => $this->xx,
            'error' => $this->error,
        ];
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

    public function zodiacSign(): int
    {
        return Angle::zodiacSign($this->longitude());
    }

    public function zodiacSignShortName(): string
    {
        return Angle::zodiacSignShortName($this->longitude());
    }

    public function zodiacSignName(): string
    {
        return Angle::zodiacSignName($this->longitude());
    }

    public function degreeInSign(): float
    {
        return Angle::degreeInSign($this->longitude());
    }

    public function angularDistanceTo(self $other): float
    {
        return Aspect::angularDistance($this->longitude(), $other->longitude());
    }

    public function nearestMajorAspectTo(self $other): Aspect
    {
        return Aspect::nearestMajor($this->longitude(), $other->longitude());
    }

    public function majorAspectTo(self $other, float $maxOrb): ?Aspect
    {
        return Aspect::majorWithinOrb($this->longitude(), $other->longitude(), $maxOrb);
    }

    public function nearestMajorOrMinorAspectTo(self $other): Aspect
    {
        return Aspect::nearestMajorOrMinor($this->longitude(), $other->longitude());
    }

    public function majorOrMinorAspectTo(self $other, float $maxOrb): ?Aspect
    {
        return Aspect::majorOrMinorWithinOrb($this->longitude(), $other->longitude(), $maxOrb);
    }

    public function aspectFromSetTo(self $other, AspectSet $aspectSet): ?Aspect
    {
        return $aspectSet->match($this->longitude(), $other->longitude());
    }

    public function aspectResultFromSetTo(self $other, AspectSet $aspectSet): ?AspectResult
    {
        $aspect = $this->aspectFromSetTo($other, $aspectSet);

        if ($aspect === null) {
            return null;
        }

        return new AspectResult(
            $aspect,
            $this->isApplyingAspectTo($other, $aspect->angle)
        );
    }

    public function majorAspectResultTo(self $other, float $maxOrb): ?AspectResult
    {
        $aspect = $this->majorAspectTo($other, $maxOrb);

        if ($aspect === null) {
            return null;
        }

        return new AspectResult(
            $aspect,
            $this->isApplyingAspectTo($other, $aspect->angle)
        );
    }

    public function isAspectTo(self $other, float $aspectAngle, float $maxOrb): bool
    {
        return Aspect::isWithinOrb($this->longitude(), $other->longitude(), $aspectAngle, $maxOrb);
    }

    public function isApplyingAspectTo(self $other, float $aspectAngle): bool
    {
        return Aspect::isApplying(
            $this->longitude(),
            $this->longitudeSpeed(),
            $other->longitude(),
            $other->longitudeSpeed(),
            $aspectAngle
        );
    }

    public function isSeparatingAspectTo(self $other, float $aspectAngle): bool
    {
        return !$this->isApplyingAspectTo($other, $aspectAngle);
    }

    public function rightAscensionHms(): string
    {
        return Angle::formatHms($this->longitude());
    }

    public function longitudeSpeedDms(): string
    {
        return Angle::formatDms($this->longitudeSpeed());
    }

    public function latitudeSpeedDms(): string
    {
        return Angle::formatDms($this->latitudeSpeed());
    }

    public function isOk(): bool
    {
        return $this->rc !== SwissDate::ERR;
    }

    public function hasError(): bool
    {
        return $this->error !== '';
    }

    public function hasFlag(int $flag): bool
    {
        return Catalog::hasFlag($this->rc, $flag);
    }

    public function isSidereal(): bool
    {
        return $this->hasFlag(Catalog::SEFLG_SIDEREAL);
    }

    public function isEquatorial(): bool
    {
        return $this->hasFlag(Catalog::SEFLG_EQUATORIAL);
    }

    public function isCartesian(): bool
    {
        return $this->hasFlag(Catalog::SEFLG_XYZ);
    }

    public function hasSpeed(): bool
    {
        return Catalog::wantsSpeed($this->rc);
    }

    public function isRadians(): bool
    {
        return $this->hasFlag(Catalog::SEFLG_RADIANS);
    }

    public function isTopocentric(): bool
    {
        return $this->hasFlag(Catalog::SEFLG_TOPOCTR);
    }
}