<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Aspect;
use SwissEph\AspectSet;

final class AspectSetTest extends TestCase
{
    public function testMajorSetMatchesMajorAspect(): void
    {
        $set = AspectSet::major(2.0);
        $aspect = $set->match(10.0, 101.5);

        self::assertNotNull($aspect);
        self::assertSame(Aspect::SQUARE, $aspect->angle);
        self::assertSame('square', $aspect->name);
    }

    public function testMajorSetIgnoresMinorAspect(): void
    {
        $set = AspectSet::major(2.0);

        self::assertNull($set->match(10.0, 56.0));
    }

    public function testMajorAndMinorSetMatchesMinorAspect(): void
    {
        $set = AspectSet::majorAndMinor(2.0);
        $aspect = $set->match(10.0, 56.0);

        self::assertNotNull($aspect);
        self::assertSame(Aspect::SEMISQUARE, $aspect->angle);
        self::assertSame('semisquare', $aspect->name);
    }

    public function testNearestIgnoresOrb(): void
    {
        $set = AspectSet::major(0.1);
        $aspect = $set->nearest(10.0, 101.5);

        self::assertSame(Aspect::SQUARE, $aspect->angle);
        self::assertEqualsWithDelta(1.5, $aspect->orb, 1e-12);
    }
}