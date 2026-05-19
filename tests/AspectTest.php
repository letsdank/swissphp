<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Aspect;

final class AspectTest extends TestCase
{
    public function testAngularDistanceUsesShortestArc(): void
    {
        self::assertSame(20.0, Aspect::angularDistance(10.0, 350.0));
        self::assertSame(20.0, Aspect::angularDistance(350.0, 10.0));
        self::assertSame(180.0, Aspect::angularDistance(0.0, 180.0));
    }

    public function testNearestMajorAspect(): void
    {
        $aspect = Aspect::nearestMajor(10.0, 101.5);

        self::assertSame(90.0, $aspect->angle);
        self::assertSame('square', $aspect->name);
        self::assertEqualsWithDelta(1.5, $aspect->orb, 1e-12);
    }

    public function testNearestMajorAspectAcrossZero(): void
    {
        $aspect = Aspect::nearestMajor(350.0, 11.0);

        self::assertSame(0.0, $aspect->angle);
        self::assertSame('conjunction', $aspect->name);
        self::assertEqualsWithDelta(21.0, $aspect->orb, 1e-12);
    }

    public function testIsWithinOrb(): void
    {
        self::assertTrue(Aspect::isWithinOrb(10.0, 101.5, Aspect::SQUARE, 2.0));
        self::assertFalse(Aspect::isWithinOrb(10.0, 101.5, Aspect::SQUARE, 1.0));
    }

    public function testMajorWithinOrbReturnsAspectWhenOrbFits(): void
    {
        $aspect = Aspect::majorWithinOrb(10.0, 101.5, 2.0);

        self::assertNotNull($aspect);
        self::assertSame(90.0, $aspect->angle);
        self::assertSame('square', $aspect->name);
        self::assertEqualsWithDelta(1.5, $aspect->orb, 1e-12);
    }

    public function testMajorWithinOrbReturnsNullWhenOrbDoesNotFit(): void
    {
        self::assertNull(Aspect::majorWithinOrb(10.0, 101.5, 1.0));
    }

    public function testIsApplyingAspect(): void
    {
        self::assertTrue(Aspect::isApplying(10.0, 1.0, 101.5, 0.0, Aspect::SQUARE));
        self::assertFalse(Aspect::isApplying(10.0, -1.0, 101.5, 0.0, Aspect::SQUARE));
    }

    public function testIsApplyingAspectAcrossZero(): void
    {
        self::assertTrue(Aspect::isApplying(350.0, 1.0, 11.0, 0.0, Aspect::CONJUNCTION));
        self::assertFalse(Aspect::isApplying(350.0, -1.0, 11.0, 0.0, Aspect::CONJUNCTION));
    }

    public function testOrbDms(): void
    {
        $aspect = new Aspect(90.0, 'square', 1.5);

        self::assertSame('1°30\'00"', $aspect->orbDms());
    }

    public function testNearestFromList(): void
    {
        $aspect = Aspect::nearestFromList(10.0, 56.0, [
            Aspect::CONJUNCTION => 'conjunction',
            Aspect::SEMISQUARE => 'semisquare',
            Aspect::SEXTILE => 'sextile',
        ]);

        self::assertSame(45.0, $aspect->angle);
        self::assertSame('semisquare', $aspect->name);
        self::assertEqualsWithDelta(1.0, $aspect->orb, 1e-12);
    }

    public function testNearestMajorOrMinorAspect(): void
    {
        $aspect = Aspect::nearestMajorOrMinor(10.0, 56.0);

        self::assertSame(45.0, $aspect->angle);
        self::assertSame('semisquare', $aspect->name);
        self::assertEqualsWithDelta(1.0, $aspect->orb, 1e-12);
    }

    public function testMajorOrMinorWithinOrb(): void
    {
        self::assertNotNull(Aspect::majorOrMinorWithinOrb(10.0, 56.0, 1.5));
        self::assertNull(Aspect::majorOrMinorWithinOrb(10.0, 56.0, 0.5));
    }
}