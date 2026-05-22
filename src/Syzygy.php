<?php

declare(strict_types=1);

namespace SwissEph;

final class Syzygy
{
    private const STEP_DAYS = 0.5;
    private const MAX_DAYS = 35.0;
    private const BISECTION_ITERATIONS = 80;
    private const PRECISION_DEGREES = 1e-9;

    public const PHASE_NEW_MOON = 0.0;
    public const PHASE_FIRST_QUARTER = 90.0;
    public const PHASE_FULL_MOON = 180.0;
    public const PHASE_LAST_QUARTER = 270.0;

    public static function nextNewMoon(
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhase(self::PHASE_NEW_MOON, $tjdEt, $flags);
    }

    public static function nextFirstQuarter(
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhase(self::PHASE_FIRST_QUARTER, $tjdEt, $flags);
    }

    public static function nextFullMoon(
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhase(self::PHASE_FULL_MOON, $tjdEt, $flags);
    }

    public static function nextLastQuarter(
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhase(self::PHASE_LAST_QUARTER, $tjdEt, $flags);
    }

    public static function nextNewMoonUt(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhaseUt(self::PHASE_NEW_MOON, $tjdUt, $flags);
    }

    public static function nextFirstQuarterUt(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhaseUt(self::PHASE_FIRST_QUARTER, $tjdUt, $flags);
    }

    public static function nextFullMoonUt(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhaseUt(self::PHASE_FULL_MOON, $tjdUt, $flags);
    }

    public static function nextLastQuarterUt(
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        return self::nextPhaseUt(self::PHASE_LAST_QUARTER, $tjdUt, $flags);
    }

    public static function nextPhaseUt(
        float $phase,
        float $tjdUt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        $tjdEt = $tjdUt + DeltaT::deltatEx($tjdUt, $flags);
        $phaseEt = self::nextPhase($phase, $tjdEt, $flags);

        if ($phaseEt < $tjdEt) {
            return $tjdUt - 1.0;
        }

        return $phaseEt - DeltaT::deltatEx($phaseEt, $flags);
    }

    public static function nextPhase(
        float $phase,
        float $tjdEt,
        int   $flags = Catalog::SEFLG_DEFAULTEPH
    ): float
    {
        $target = Angle::degnorm($phase);
        $flags = Catalog::normalizeEphemerisFlags($flags) | Catalog::SEFLG_SPEED;

        try {
            $left = $tjdEt;
            $leftPhase = self::phaseAngle($left, $flags);
        } catch (\InvalidArgumentException) {
            return $tjdEt - 1.0;
        }

        $targetUnwrapped = $target;

        while ($targetUnwrapped <= $leftPhase + 1e-12) {
            $targetUnwrapped += 360.0;
        }

        $leftUnwrapped = $leftPhase;
        $leftValue = $leftUnwrapped - $targetUnwrapped;
        $end = $tjdEt + self::MAX_DAYS;

        for ($right = $tjdEt + self::STEP_DAYS; $right <= $end + 1e-12; $right += self::STEP_DAYS) {
            try {
                $rightPhase = self::phaseAngle($right, $flags);
            } catch (\InvalidArgumentException) {
                return $tjdEt - 1.0;
            }

            $rightUnwrapped = self::unwrapNear($rightPhase, $leftUnwrapped);
            $rightValue = $rightUnwrapped - $targetUnwrapped;

            if ($rightValue >= 0.0) {
                return self::refine($left, $right, $targetUnwrapped, $leftUnwrapped, $flags);
            }

            $left = $right;
            $leftUnwrapped = $rightUnwrapped;
            $leftValue = $rightValue;
        }

        return $tjdEt - 1.0;
    }

    private static function refine(
        float $left,
        float $right,
        float $targetUnwrapped,
        float $leftUnwrapped,
        int   $flags
    ): float
    {
        $leftValue = $leftUnwrapped - $targetUnwrapped;

        for ($i = 0; $i < self::BISECTION_ITERATIONS; $i++) {
            $mid = ($left + $right) / 2.0;
            $midPhase = self::phaseAngle($mid, $flags);
            $midUnwrapped = self::unwrapNear($midPhase, $leftUnwrapped);
            $midValue = $midUnwrapped - $targetUnwrapped;

            if (abs($midValue) < self::PRECISION_DEGREES) {
                return $mid;
            }

            if ($leftValue <= 0.0 && $midValue >= 0.0) {
                $right = $mid;
                continue;
            }

            $left = $mid;
            $leftUnwrapped = $midUnwrapped;
            $leftValue = $midValue;
        }

        return ($left + $right) / 2.0;
    }

    private static function phaseAngle(float $tjdEt, int $flags): float
    {
        $moon = Calculator::calcApparentFlags($tjdEt, Catalog::SE_MOON, $flags);
        $sun = Calculator::calcApparentFlags($tjdEt, Catalog::SE_SUN, $flags);

        if ($moon['rc'] === SwissDate::ERR || $sun['rc'] === SwissDate::ERR) {
            throw new \InvalidArgumentException($moon['error'] !== '' ? $moon['error'] : $sun['error']);
        }

        return Angle::degnorm($moon['xx'][0] - $sun['xx'][0]);
    }

    private static function unwrapNear(float $phase, float $reference): float
    {
        while ($phase - $reference > 180.0) {
            $phase -= 360.0;
        }

        while ($phase - $reference <= -180.0) {
            $phase += 360.0;
        }

        return $phase;
    }

    private static function crossed(float $left, float $right): bool
    {
        return $left === 0.0
            || $right === 0.0
            || ($left < 0.0 && $right > 0.0)
            || ($left > 0.0 && $right < 0.0);
    }
}