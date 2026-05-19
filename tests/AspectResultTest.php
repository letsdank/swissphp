<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Aspect;
use SwissEph\AspectResult;

final class AspectResultTest extends TestCase
{
    public function testAccessors(): void
    {
        $aspect = new Aspect(90.0, 'square', 1.5);
        $result = new AspectResult($aspect, true);

        self::assertSame($aspect, $result->aspect);
        self::assertTrue($result->isApplying());
        self::assertFalse($result->isSeparating());
        self::assertSame('square', $result->name());
        self::assertSame(90.0, $result->angle());
        self::assertSame(1.5, $result->orb());
        self::assertSame('1°30\'00"', $aspect->orbDms());
    }

    public function testSeparating(): void
    {
        $result = new AspectResult(new Aspect(90.0, 'square', 1.5), false);

        self::assertFalse($result->isApplying());
        self::assertTrue($result->isSeparating());
    }
}