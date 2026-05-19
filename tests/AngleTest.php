<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Angle;

final class AngleTest extends TestCase
{
    public function testDegnorm(): void
    {
        self::assertSame(0.0, Angle::degnorm(360.0));
        self::assertSame(359.0, Angle::degnorm(-1.0));
        self::assertSame(1.25, Angle::degnorm(721.25));
        self::assertSame(0.0, Angle::degnorm(-0.00000000000001));
    }

    public function testRadnorm(): void
    {
        self::assertEqualsWithDelta(0.0, Angle::radnorm(2.0 * M_PI), 1e-14);
        self::assertEqualsWithDelta(1.0, Angle::radnorm(1.0), 1e-14);
        self::assertEqualsWithDelta(2.0 * M_PI - 1.0, Angle::radnorm(-1.0), 1e-14);
    }

    public function testAngularDifferences(): void
    {
        self::assertSame(20.0, Angle::difdegn(10.0, 350.0));
        self::assertSame(20.0, Angle::difdeg2n(10.0, 350.0));
        self::assertSame(-20.0, Angle::difdeg2n(350.0, 10.0));
        self::assertSame(-180.0, Angle::difdeg2n(180.0, 0.0));
    }

    public function testMidpointAcrossZero(): void
    {
        self::assertSame(0.0, Angle::degMidp(10.0, 350.0));
        self::assertSame(0.0, Angle::degMidp(350.0, 10.0));
        self::assertSame(90.0, Angle::degMidp(120.0, 60.0));
    }

    public function testSplitDegWithoutRounding(): void
    {
        $result = Angle::splitDeg(12.3456, 0);

        self::assertSame(12, $result['deg']);
        self::assertSame(20, $result['min']);
        self::assertSame(44, $result['sec']);
        self::assertEqualsWithDelta(0.16, $result['secfr'], 1e-10);
        self::assertSame(1, $result['sign']);
    }

    public function testSplitDegKeepsNegativeSign(): void
    {
        $result = Angle::splitDeg(-12.5, 0);

        self::assertSame(12, $result['deg']);
        self::assertSame(30, $result['min']);
        self::assertSame(0, $result['sec']);
        self::assertSame(-1, $result['sign']);
    }

    public function testSplitDegZodiacal(): void
    {
        $result = Angle::splitDeg(33.5, Angle::SPLIT_DEG_ZODIACAL);

        self::assertSame(3, $result['deg']);
        self::assertSame(30, $result['min']);
        self::assertSame(0, $result['sec']);
        self::assertSame(1, $result['sign']);
    }

    public function testSplitDegRoundSecondWrapsZodiac(): void
    {
        $result = Angle::splitDeg(
            359.9999,
            Angle::SPLIT_DEG_ZODIACAL | Angle::SPLIT_DEG_ROUND_SEC
        );

        self::assertSame(0, $result['sign']);
        self::assertSame(0, $result['deg']);
        self::assertSame(0, $result['min']);
        self::assertSame(0, $result['sec']);
    }

    public function testFormatDms(): void
    {
        self::assertSame('12°20\'44"', Angle::formatDms(12.3456));
        self::assertSame('-12°30\'00"', Angle::formatDms(-12.5));
    }

    public function testFormatZodiac(): void
    {
        self::assertSame('10 cp 22\'08"', Angle::formatZodiac(280.3689187));
        self::assertSame('0 ar 00\'00"', Angle::formatZodiac(359.999999));
    }

    public function testFormatHms(): void
    {
        self::assertSame('18h41m29s', Angle::formatHms(280.3689187));
        self::assertSame('00h00m00s', Angle::formatHms(360.0));
    }

    public function testZodiacSignHelpers(): void
    {
        self::assertSame(0, Angle::zodiacSign(0.0));
        self::assertSame(0, Angle::zodiacSign(29.999999));
        self::assertSame(1, Angle::zodiacSign(30.0));
        self::assertSame(11, Angle::zodiacSign(359.999999));
        self::assertSame(11, Angle::zodiacSign(-0.000001));

        self::assertSame('ar', Angle::zodiacSignShortName(0.0));
        self::assertSame('cp', Angle::zodiacSignShortName(280.0));
        self::assertSame('Capricorn', Angle::zodiacSignName(280.0));

        self::assertEqualsWithDelta(10.3689187, Angle::degreeInSign(280.3689187), 1e-12);
    }
}