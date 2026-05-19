<?php

namespace SwissEph\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SwissEph\DeltaT;

final class DeltaTTest extends TestCase
{
    #[DataProvider('defaultDeltaTProvider')]
    public function testDefaultDeltaTMatchesSwissEphemerisFixtures(float $jd, float $expected): void
    {
        self::assertEqualsWithDelta($expected, DeltaT::deltat($jd), 1e-14);
    }

    #[DataProvider('deltaTExProvider')]
    public function testDeltaTExMatchesSwissEphemerisFixtures(int $iflag, float $jd, float $expected): void
    {
        self::assertEqualsWithDelta($expected, DeltaT::deltatEx($jd, $iflag), 1e-14);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function defaultDeltaTProvider(): iterable
    {
        yield '1698-10-09' => [2341524.0, 0.00017042089587131256];
        yield '-1501-12-18' => [1173182.5, 0.40946234392815261671];
        yield '1959-06-04' => [2436723.5, 0.00038058436301841741];
        yield '2000-01-07' => [2451550.5, 0.00073881327677003718];
    }

    /**
     * @return iterable<string, array{int, float, float}>
     */
    public static function deltaTExProvider(): iterable
    {
        yield 'SWIEPH 1698' => [DeltaT::SEFLG_SWIEPH, 2341524.0, 0.00017042089587131256];
        yield 'JPLEPH 1698' => [DeltaT::SEFLG_JPLEPH, 2341524.0, 0.00017042089587131256];
        yield 'MOSEPH 1698' => [DeltaT::SEFLG_MOSEPH, 2341524.0, 0.00015520838090089632];

        yield 'SWIEPH ancient' => [DeltaT::SEFLG_SWIEPH, 1173182.5, 0.40946234392815261671];
        yield 'JPLEPH ancient' => [DeltaT::SEFLG_JPLEPH, 1173182.5, 0.40946234392815261671];
        yield 'MOSEPH ancient' => [DeltaT::SEFLG_MOSEPH, 1173182.5, 0.40669632021936935606];

        yield 'MOSEPH 1959' => [DeltaT::SEFLG_MOSEPH, 2436723.5, 0.00038058436301841741];
        yield 'MOSEPH 2000' => [DeltaT::SEFLG_MOSEPH, 2451550.5, 0.00073881327677003718];
    }
}