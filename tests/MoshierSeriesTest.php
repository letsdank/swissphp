<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Moshier;
use SwissEph\MoshierSeries;

final class MoshierSeriesTest extends TestCase
{
    public function testPolynomialTermIsEvaluatedListSwissEphemeris(): void
    {
        $table = [
            'distance' => 2.0,
            'maxHarmonic' => [0, 0, 0, 0, 0, 0, 0, 0, 0],
            'argTable' => [
                0, 1,
                -1,
            ],
            'lonTable' => [10.0, 100.0],
            'latTable' => [2.0, -1.0],
            'radTable' => [0.1, 0.2],
        ];

        $position = MoshierSeries::evaluatePolar(Moshier::J2000, $table);

        self::assertEqualsWithDelta(100.0 * Moshier::ARCSEC_TO_RAD, $position[0], 1e-15);
        self::assertEqualsWithDelta(-1.0 * Moshier::ARCSEC_TO_RAD, $position[1], 1e-15);
        self::assertEqualsWithDelta(2.0 + 2.0 * 0.2 * Moshier::ARCSEC_TO_RAD, $position[2], 1e-15);

        $position = MoshierSeries::evaluatePolar(Moshier::J2000 + Moshier::TIMESCALE, $table);

        self::assertEqualsWithDelta(110.0 * Moshier::ARCSEC_TO_RAD, $position[0], 1e-15);
        self::assertEqualsWithDelta(1.0 * Moshier::ARCSEC_TO_RAD, $position[1], 1e-15);
        self::assertEqualsWithDelta(2.0 + 2.0 * 0.3 * Moshier::ARCSEC_TO_RAD, $position[2], 1e-15);
    }

    public function testLongitudePolynomialIsNormalizedWithMods3600(): void
    {
        $table = [
            'distance' => 1.0,
            'maxHarmonic' => [0, 0, 0, 0, 0, 0, 0, 0, 0],
            'argTable' => [
                0, 0,
                -1,
            ],
            'lonTable' => [1296001.0],
            'latTable' => [0.0],
            'radTable' => [0.0],
        ];

        $position = MoshierSeries::evaluatePolar(Moshier::J2000, $table);

        self::assertEqualsWithDelta(1.0 * Moshier::ARCSEC_TO_RAD, $position[0], 1e-15);
        self::assertSame(0.0, $position[1]);
        self::assertSame(1.0, $position[2]);
    }

    public function testPeriodicTermIsEvaluatedWithFundamentalArgument(): void
    {
        $table = [
            'distance' => 1.5,
            'maxHarmonic' => [0, 0, 1, 0, 0, 0, 0, 0, 0],
            'argTable' => [
                1,
                1, 3,
                0,
                -1,
            ],
            'lonTable' => [5.0, 7.0],
            'latTable' => [-2.0, 3.0],
            'radTable' => [0.5, -0.25],
        ];

        $argument = Moshier::meanArgument(2, Moshier::J2000);
        $cosArgument = cos($argument);
        $sinArgument = sin($argument);

        $position = MoshierSeries::evaluatePolar(Moshier::J2000, $table);

        $expectedLongitude = 5.0 * $cosArgument + 7.0 * $sinArgument;
        $expectedLatitude = -2.0 * $cosArgument + 3.0 * $sinArgument;
        $expectedRadius = 0.5 * $cosArgument - 0.25 * $sinArgument;

        self::assertEqualsWithDelta($expectedLongitude * Moshier::ARCSEC_TO_RAD, $position[0], 1e-15);
        self::assertEqualsWithDelta($expectedLatitude * Moshier::ARCSEC_TO_RAD, $position[1], 1e-15);
        self::assertEqualsWithDelta(1.5 + 1.5 * $expectedRadius * Moshier::ARCSEC_TO_RAD, $position[2], 1e-15);
    }

    public function testPeriodicTermCombinesPositionAndNegativeArguments(): void
    {
        $table = [
            'distance' => 1.0,
            'maxHarmonic' => [1, 0, 1, 0, 0, 0, 0, 0, 0],
            'argTable' => [
                2,
                1, 3,
                -1, 1,
                0,
                -1,
            ],
            'lonTable' => [0.0, 10.0],
            'latTable' => [0.0, 0.0],
            'radTable' => [0.0, 0.0],
        ];

        $combinedArgument = Moshier::meanArgument(2, Moshier::J2000)
            - Moshier::meanArgument(0, Moshier::J2000);

        $position = MoshierSeries::evaluatePolar(Moshier::J2000, $table);

        self::assertEqualsWithDelta(
            10.0 * sin($combinedArgument) * Moshier::ARCSEC_TO_RAD,
            $position[0],
            1e-15
        );
        self::assertSame(0.0, $position[1]);
        self::assertSame(1.0, $position[2]);
    }

    public function testUnexpectedEndOfArgumentTableThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoshierSeries::evaluatePolar(Moshier::J2000, [
            'distance' => 1.0,
            'maxHarmonic' => [0, 0, 0, 0, 0, 0, 0, 0, 0],
            'argTable' => [0],
            'lonTable' => [],
            'latTable' => [],
            'radTable' => [],
        ]);
    }

    public function testMissingHarmonicThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoshierSeries::evaluatePolar(Moshier::J2000, [
            'distance' => 1.0,
            'maxHarmonic' => [0, 0, 0, 0, 0, 0, 0, 0, 0],
            'argTable' => [
                1,
                1, 1,
                0,
                -1,
            ],
            'lonTable' => [0.0, 0.0],
            'latTable' => [0.0, 0.0],
            'radTable' => [0.0, 0.0],
        ]);
    }
}