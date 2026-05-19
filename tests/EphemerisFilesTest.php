<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\EphemerisFiles;

final class EphemerisFilesTest extends TestCase
{
    private const FALLBACK_EPHE_PATH = '/home/dank/Документы/src/astroprocessor/resources/data/ephe';

    public function testPathCanBeConfigured(): void
    {
        $path = $this->ephePath();

        EphemerisFiles::setPath($path);

        self::assertSame($path, EphemerisFiles::path());
    }

    public function testPlanetFileCanBeResolvedForJ2000(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame('sepl_18.se1', $result['file']);
        self::assertFileExists($result['path']);
    }

    public function testMoonFileCanBeResolvedForJ2000(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame('semo_18.se1', $result['file']);
        self::assertFileExists($result['path']);
    }

    public function testAsteroidFileCanBeResolvedForJ2000(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame('seas_18.se1', $result['file']);
        self::assertFileExists($result['path']);
    }

    public function testResolveForBodySelectsMoonFile(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::resolveForBody(Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(EphemerisFiles::TYPE_MOON, $result['type']);
        self::assertSame('semo_18.se1', $result['file']);
    }

    public function testResolveReturnsErrorWhenPathIsMissing(): void
    {
        EphemerisFiles::setPath('/tmp/swissphp-missing-ephe-path');

        $result = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('sepl_18.se1', $result['file']);
        self::assertSame('ephemeris file not found', $result['error']);
    }

    public function testHeaderReadsSwissEphemerisTextHeader(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $header = EphemerisFiles::header($resolved['path']);

        self::assertSame(Catalog::SE_OK, $header['rc']);
        self::assertSame('SWISSEPH  1', $header['version']);
        self::assertSame('sepl_18.se1', $header['fileLine']);
        self::assertStringContainsString('Astrodienst', $header['copyright']);
    }

    public function testFilesListsEphemerisFiles(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $files = EphemerisFiles::files();

        self::assertContains('sepl_18.se1', $files);
        self::assertContains('semo_18.se1', $files);
        self::assertContains('seas_18.se1', $files);
    }

    private function ephePath(): string
    {
        $path = getenv('SWISSPHP_EPHE_PATH') ?: self::FALLBACK_EPHE_PATH;

        if (!is_dir($path)) {
            self::markTestSkipped('Ephemeris files are not available: ' . $path);
        }

        return $path;
    }
}