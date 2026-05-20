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

    public function testPlanetMetadataCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $metadata = EphemerisFiles::metadata($resolved['path']);

        self::assertSame(Catalog::SE_OK, $metadata['rc']);
        self::assertSame('little', $metadata['endian']);
        self::assertSame('sepl_18.se1', $metadata['file']);
        self::assertSame(431, $metadata['denum']);
        self::assertSame(10, $metadata['nplan']);
        self::assertSame([2, 3, 0, 4, 5, 6, 7, 8, 9, 10], $metadata['ipl']);
        self::assertSame($metadata['actualFileLength'], $metadata['fileLength']);
        self::assertEqualsWithDelta(2378496.5, $metadata['tfstart'], 1e-9);
        self::assertGreaterThan(2451545.0, $metadata['tfend']);
        self::assertTrue(EphemerisFiles::containsDate($metadata, 2451545.0));
        self::assertTrue(EphemerisFiles::containsPlanet($metadata, Catalog::SE_MERCURY));
    }

    public function testMoonMetadataCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $metadata = EphemerisFiles::metadata($resolved['path']);

        self::assertSame(Catalog::SE_OK, $metadata['rc']);
        self::assertSame('semo_18.se1', $metadata['file']);
        self::assertSame(431, $metadata['denum']);
        self::assertSame(1, $metadata['nplan']);
        self::assertSame([Catalog::SE_MOON], $metadata['ipl']);
        self::assertTrue(EphemerisFiles::containsDate($metadata, 2451545.0));
        self::assertTrue(EphemerisFiles::containsPlanet($metadata, Catalog::SE_MOON));
    }

    public function testMainAsteroidMetadataCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $metadata = EphemerisFiles::metadata($resolved['path']);

        self::assertSame(Catalog::SE_OK, $metadata['rc']);
        self::assertSame('seas_18.se1', $metadata['file']);
        self::assertSame(431, $metadata['denum']);
        self::assertSame(6, $metadata['nplan']);
        self::assertSame([12, 13, 14, 15, 16, 17], $metadata['ipl']);
        self::assertTrue(EphemerisFiles::containsDate($metadata, 2451545.0));
    }

    public function testResolveWithMetadataChecksFileDateRange(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::resolveWithMetadata(EphemerisFiles::TYPE_PLANET, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertNotNull($result['metadata']);
        self::assertSame('sepl_18.se1', $result['file']);
    }

    public function testMetadataReturnsErrorForMissingFile(): void
    {
        $metadata = EphemerisFiles::metadata('/tmp/swissphp-missing-file.se1');

        self::assertSame(Catalog::SE_ERR, $metadata['rc']);
        self::assertSame('ephemeris file not found', $metadata['error']);
    }

    public function testPlanetBodyDescriptorsCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::bodyDescriptors($resolved['path']);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertCount(10, $result['descriptors']);

        $mercury = $result['descriptors'][0];

        self::assertSame(Catalog::SE_MERCURY, $mercury['ipl']);
        self::assertSame(3318, $mercury['lndx0']);
        self::assertSame(15, $mercury['flags']);
        self::assertSame(39, $mercury['ncoe']);
        self::assertSame(1.5, $mercury['rmax']);
        self::assertTrue($mercury['isHeliocentric']);
        self::assertTrue($mercury['isRotated']);
        self::assertTrue($mercury['usesReferenceEllipse']);
        self::assertTrue($mercury['isEmbHeliocentric']);
        self::assertSame(78, $mercury['refepCount']);
        self::assertCount(78, $mercury['refep']);
        self::assertEqualsWithDelta(2378487.7270665597, $mercury['tfstart'], 1e-9);
        self::assertEqualsWithDelta(87.96934964454672, $mercury['dseg'], 1e-12);
    }

    public function testMoonBodyDescriptorCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::bodyDescriptor($resolved['path'], Catalog::SE_MOON);

        self::assertSame(Catalog::SE_OK, $result['rc']);

        $moon = $result['descriptor'];

        self::assertSame(Catalog::SE_MOON, $moon['ipl']);
        self::assertSame(746, $moon['lndx0']);
        self::assertSame(14, $moon['flags']);
        self::assertSame(29, $moon['ncoe']);
        self::assertSame(0.004, $moon['rmax']);
        self::assertFalse($moon['isHeliocentric']);
        self::assertTrue($moon['isRotated']);
        self::assertTrue($moon['usesReferenceEllipse']);
        self::assertTrue($moon['isEmbHeliocentric']);
        self::assertSame(58, $moon['refepCount']);
        self::assertCount(58, $moon['refep']);
        self::assertEqualsWithDelta(27.5545514309491, $moon['dseg'], 1e-12);
    }

    public function testMainAsteroidBodyDescriptorCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::bodyDescriptor($resolved['path'], Catalog::SE_MEAN_APOG);

        self::assertSame(Catalog::SE_OK, $result['rc']);

        $descriptor = $result['descriptor'];

        self::assertSame(Catalog::SE_MEAN_APOG, $descriptor['ipl']);
        self::assertSame(742, $descriptor['lndx0']);
        self::assertSame(8, $descriptor['flags']);
        self::assertSame(26, $descriptor['ncoe']);
        self::assertSame(60.0, $descriptor['rmax']);
        self::assertFalse($descriptor['usesReferenceEllipse']);
        self::assertSame(0, $descriptor['refepCount']);
        self::assertSame([], $descriptor['refep']);
        self::assertEqualsWithDelta(1000.0, $descriptor['dseg'], 1e-12);
    }

    public function testBodyDescriptorReturnsErrorForMissingBody(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::bodyDescriptor($resolved['path'], Catalog::SE_MOON);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('ephemeris body descriptor not found', $result['error']);
    }

    public function testMercurySegmentIndexEntryCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $entry['rc']);
        self::assertSame(Catalog::SE_MERCURY, $entry['ipl']);
        self::assertSame(830, $entry['segment']);
        self::assertSame(5808, $entry['indexOffset']);
        self::assertSame(85939, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451502.2872715, $entry['tseg0'], 1e-7);
        self::assertEqualsWithDelta(2451590.2566212, $entry['tseg1'], 1e-7);
    }

    public function testMoonSegmentIndexEntryCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $entry['rc']);
        self::assertSame(Catalog::SE_MOON, $entry['ipl']);
        self::assertSame(2651, $entry['segment']);
        self::assertSame(8699, $entry['indexOffset']);
        self::assertSame(450929, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451534.6712141, $entry['tseg0'], 1e-7);
        self::assertEqualsWithDelta(2451562.2257656, $entry['tseg1'], 1e-7);
    }

    public function testMainAsteroidSegmentIndexEntryCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $entry['rc']);
        self::assertSame(Catalog::SE_MEAN_APOG, $entry['ipl']);
        self::assertSame(73, $entry['segment']);
        self::assertSame(961, $entry['indexOffset']);
        self::assertSame(12534, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451496.5, $entry['tseg0'], 1e-9);
        self::assertEqualsWithDelta(2452496.5, $entry['tseg1'], 1e-9);
    }

    public function testSegmentIndexEntryReturnsErrorOutsideBodyRange(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MERCURY, 3000000.0);

        self::assertSame(Catalog::SE_ERR, $entry['rc']);
        self::assertSame('julian day is outside ephemeris body range', $entry['error']);
    }

    public function testMercurySegmentCoefficientsCanBeDecoded(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(85939, $result['segmentOffset']);
        self::assertSame(86014, $result['nextOffset']);
        self::assertSame([[0, 0, 10, 8], [0, 1, 9, 7], [0, 0, 4, 5]], $result['coordinateSizes']);

        self::assertCount(3, $result['coefficients']);
        self::assertCount(39, $result['coefficients'][0]);

        self::assertEqualsWithDelta(-1.539e-6, $result['coefficients'][0][0], 1e-15);
        self::assertEqualsWithDelta(-3.6045e-6, $result['coefficients'][0][1], 1e-15);
        self::assertEqualsWithDelta(2.727075e-5, $result['coefficients'][1][0], 1e-15);
        self::assertEqualsWithDelta(-1.05e-8, $result['coefficients'][2][0], 1e-18);
    }

    public function testMoonSegmentCoefficientsCanBeDecoded(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(450929, $result['segmentOffset']);
        self::assertSame(451088, $result['nextOffset']);
        self::assertSame([[4, 8, 7, 5], [2, 10, 6, 7], [0, 7, 5, 6]], $result['coordinateSizes']);

        self::assertCount(29, $result['coefficients'][0]);

        self::assertEqualsWithDelta(5.6053838000000006e-5, $result['coefficients'][0][0], 1e-18);
        self::assertEqualsWithDelta(-4.474466e-6, $result['coefficients'][0][1], 1e-18);
        self::assertEqualsWithDelta(-3.4235956e-5, $result['coefficients'][1][0], 1e-18);
        self::assertEqualsWithDelta(-1.6184660000000002e-6, $result['coefficients'][2][0], 1e-18);
    }

    public function testExtendedSegmentCoefficientHeaderCanBeDecoded(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(12534, $result['segmentOffset']);
        self::assertSame(12637, $result['nextOffset']);
        self::assertSame([[2, 2, 2, 9, 4, 6], [2, 2, 2, 10, 2, 7], [1, 3, 2, 8, 2, 9]], $result['coordinateSizes']);

        self::assertCount(26, $result['coefficients'][0]);

        self::assertEqualsWithDelta(-2.36679171, $result['coefficients'][0][0], 1e-12);
        self::assertEqualsWithDelta(2.6142296700000003, $result['coefficients'][0][1], 1e-12);
        self::assertEqualsWithDelta(-19.77715269, $result['coefficients'][1][0], 1e-12);
        self::assertEqualsWithDelta(-6.33939567, $result['coefficients'][2][0], 1e-12);
    }

    public function testSegmentCoefficientsReturnErrorForMissingBody(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('ephemeris body descriptor not found', $result['error']);
    }

    public function testMercuryRawSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(830, $result['segment']);
        self::assertEqualsWithDelta(-0.02891794383020707, $result['t'], 1e-14);

        self::assertEqualsWithDelta(-3.704804443013136e-6, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(9.742794757673299e-6, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(-1.2083717227212268e-7, $result['vector'][2], 1e-20);
        self::assertEqualsWithDelta(4.913872082244307e-7, $result['vector'][3], 1e-19);
        self::assertEqualsWithDelta(-3.315312914205531e-7, $result['vector'][4], 1e-19);
        self::assertEqualsWithDelta(1.8695265654069076e-8, $result['vector'][5], 1e-20);
    }

    public function testMoonRawSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(2651, $result['segment']);
        self::assertEqualsWithDelta(-0.2503027401518706, $result['t'], 1e-14);

        self::assertEqualsWithDelta(-8.404490630540918e-7, $result['vector'][0], 1e-19);
        self::assertEqualsWithDelta(-2.4459551160174223e-5, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(4.108361474314326e-6, $result['vector'][2], 1e-18);
        self::assertEqualsWithDelta(4.40431172299435e-6, $result['vector'][3], 1e-18);
        self::assertEqualsWithDelta(4.256962239948033e-6, $result['vector'][4], 1e-18);
        self::assertEqualsWithDelta(-9.084833123206908e-7, $result['vector'][5], 1e-19);
    }

    public function testRawSegmentVectorCanSkipSpeed(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MERCURY, 2451545.0, false);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertEqualsWithDelta(-3.704804443013136e-6, $result['vector'][0], 1e-18);
        self::assertSame(0.0, $result['vector'][3]);
        self::assertSame(0.0, $result['vector'][4]);
        self::assertSame(0.0, $result['vector'][5]);
    }

    public function testAsteroidRawSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(73, $result['segment']);
        self::assertEqualsWithDelta(-0.903, $result['t'], 1e-15);

        self::assertEqualsWithDelta(-3.5295974023115098, $result['vector'][0], 1e-12);
        self::assertEqualsWithDelta(-8.675404287753128, $result['vector'][1], 1e-12);
        self::assertEqualsWithDelta(-2.935899333141072, $result['vector'][2], 1e-12);
        self::assertEqualsWithDelta(0.004971228697404033, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(-0.003626425056687955, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(-0.000825794577203404, $result['vector'][5], 1e-15);
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