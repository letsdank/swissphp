<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Moshier;
use SwissEph\MoshierPlanetTables;
use SwissEph\MoshierSeries;

final class MoshierPlanetTablesTest extends TestCase
{
    public function testEarthTableMetadataMatchesSwissEphemeris(): void
    {
        $table = MoshierPlanetTables::earth();

        self::assertSame([1, 9, 14, 17, 5, 5, 2, 1, 0], $table['maxHarmonic']);
        self::assertSame(4, $table['maxPower']);
        self::assertSame(1.0, $table['distance']);

        self::assertCount(819, $table['argTable']);
        self::assertCount(460, $table['lonTable']);
        self::assertCount(460, $table['latTable']);
        self::assertCount(460, $table['radTable']);
    }

    public function testEarthTableEvaluatesAtJ2000(): void
    {
        $position = MoshierSeries::evaluatePolar(Moshier::J2000, MoshierPlanetTables::earth());

        self::assertEqualsWithDelta(deg2rad(100.379416738990), $position[0], 1e-14);
        self::assertEqualsWithDelta(deg2rad(-0.000058804993), $position[1], 1e-14);
        self::assertEqualsWithDelta(0.983309963740728, $position[2], 1e-14);
    }

    public function testAllPlanetTablesCanBeEvaluated(): void
    {
        for ($planet = MoshierPlanetTables::MERCURY; $planet <= MoshierPlanetTables::PLUTO; $planet++) {
            $position = MoshierSeries::evaluatePolar(Moshier::J2000, MoshierPlanetTables::planet($planet));

            self::assertCount(3, $position);
            self::assertGreaterThan(0.0, $position[2]);
        }
    }

    public function testUnknownPlanetThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MoshierPlanetTables::planet(999);
    }
}