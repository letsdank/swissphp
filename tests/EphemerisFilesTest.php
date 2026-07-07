<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use PHPUnit\Framework\TestCase;
use SwissEph\Catalog;
use SwissEph\EphemerisFiles;
use SwissEph\Tests\Support\EphemerisFixtureFactory;

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

    public function testFileDataForBodyReturnsEphemerisFileRange(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::fileDataForBody(Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_MERCURY, $result['body']);
        self::assertSame(EphemerisFiles::TYPE_PLANET, $result['type']);
        self::assertSame('sepl_18.se1', $result['file']);
        self::assertFileExists($result['path']);
        self::assertEqualsWithDelta(2451540.0, $result['tfstart'], 1e-9);
        self::assertEqualsWithDelta(2451550.0, $result['tfend'], 1e-9);
        self::assertSame(431, $result['denum']);
        self::assertSame('', $result['error']);
    }

    public function testFileDataForBodyMapsAsteroidFileBodyNumbers(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::fileDataForBody(Catalog::SE_AST_OFFSET + 1, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_AST_OFFSET + 1, $result['body']);
        self::assertSame(EphemerisFiles::TYPE_ASTEROID, $result['type']);
        self::assertSame('seas_18.se1', $result['file']);
        self::assertSame(431, $result['denum']);
    }

    public function testFileDataForBodyReturnsErrorWhenFileIsMissing(): void
    {
        EphemerisFiles::setPath('/tmp/swissphp-missing-ephe-path');

        $result = EphemerisFiles::fileDataForBody(Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('sepl_18.se1', $result['file']);
        self::assertSame(0.0, $result['tfstart']);
        self::assertSame(0.0, $result['tfend']);
        self::assertSame(0, $result['denum']);
        self::assertSame('ephemeris file not found', $result['error']);
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
        self::assertStringContainsString('Synthetic SwissPHP', $header['copyright']);
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
        self::assertSame(1, $metadata['nplan']);
        self::assertSame([Catalog::SE_MERCURY], $metadata['ipl']);
        self::assertSame($metadata['actualFileLength'], $metadata['fileLength']);
        self::assertEqualsWithDelta(2451540.0, $metadata['tfstart'], 1e-9);
        self::assertEqualsWithDelta(2451550.0, $metadata['tfend'], 1e-9);
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
        self::assertSame(1, $metadata['nplan']);
        self::assertSame([Catalog::SE_MEAN_APOG], $metadata['ipl']);
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
        self::assertCount(1, $result['descriptors']);

        $mercury = $result['descriptors'][0];

        self::assertSame(Catalog::SE_MERCURY, $mercury['ipl']);
        self::assertSame(2048, $mercury['lndx0']);
        self::assertSame(15, $mercury['flags']);
        self::assertSame(3, $mercury['ncoe']);
        self::assertSame(1000.0, $mercury['rmax']);
        self::assertTrue($mercury['isHeliocentric']);
        self::assertTrue($mercury['isRotated']);
        self::assertTrue($mercury['usesReferenceEllipse']);
        self::assertTrue($mercury['isEmbHeliocentric']);
        self::assertSame(6, $mercury['refepCount']);
        self::assertCount(6, $mercury['refep']);
        self::assertEqualsWithDelta(2451540.0, $mercury['tfstart'], 1e-9);
        self::assertEqualsWithDelta(10.0, $mercury['dseg'], 1e-12);
    }

    public function testMoonBodyDescriptorCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::bodyDescriptor($resolved['path'], Catalog::SE_MOON);

        self::assertSame(Catalog::SE_OK, $result['rc']);

        $moon = $result['descriptor'];

        self::assertSame(Catalog::SE_MOON, $moon['ipl']);
        self::assertSame(2048, $moon['lndx0']);
        self::assertSame(14, $moon['flags']);
        self::assertSame(3, $moon['ncoe']);
        self::assertSame(1.0, $moon['rmax']);
        self::assertFalse($moon['isHeliocentric']);
        self::assertTrue($moon['isRotated']);
        self::assertTrue($moon['usesReferenceEllipse']);
        self::assertTrue($moon['isEmbHeliocentric']);
        self::assertSame(6, $moon['refepCount']);
        self::assertCount(6, $moon['refep']);
        self::assertEqualsWithDelta(10.0, $moon['dseg'], 1e-12);
    }

    public function testMainAsteroidBodyDescriptorCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::bodyDescriptor($resolved['path'], Catalog::SE_MEAN_APOG);

        self::assertSame(Catalog::SE_OK, $result['rc']);

        $descriptor = $result['descriptor'];

        self::assertSame(Catalog::SE_MEAN_APOG, $descriptor['ipl']);
        self::assertSame(2048, $descriptor['lndx0']);
        self::assertSame(8, $descriptor['flags']);
        self::assertSame(3, $descriptor['ncoe']);
        self::assertSame(10.0, $descriptor['rmax']);
        self::assertFalse($descriptor['usesReferenceEllipse']);
        self::assertSame(0, $descriptor['refepCount']);
        self::assertSame([], $descriptor['refep']);
        self::assertEqualsWithDelta(10.0, $descriptor['dseg'], 1e-12);
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
        self::assertSame(0, $entry['segment']);
        self::assertSame(2048, $entry['indexOffset']);
        self::assertSame(3072, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451540.0, $entry['tseg0'], 1e-9);
        self::assertEqualsWithDelta(2451550.0, $entry['tseg1'], 1e-9);
    }

    public function testMoonSegmentIndexEntryCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $entry['rc']);
        self::assertSame(Catalog::SE_MOON, $entry['ipl']);
        self::assertSame(0, $entry['segment']);
        self::assertSame(2048, $entry['indexOffset']);
        self::assertSame(3072, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451540.0, $entry['tseg0'], 1e-9);
        self::assertEqualsWithDelta(2451550.0, $entry['tseg1'], 1e-9);
    }

    public function testMainAsteroidSegmentIndexEntryCanBeRead(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $entry = EphemerisFiles::segmentIndexEntry($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $entry['rc']);
        self::assertSame(Catalog::SE_MEAN_APOG, $entry['ipl']);
        self::assertSame(0, $entry['segment']);
        self::assertSame(2048, $entry['indexOffset']);
        self::assertSame(3072, $entry['segmentOffset']);
        self::assertEqualsWithDelta(2451540.0, $entry['tseg0'], 1e-9);
        self::assertEqualsWithDelta(2451550.0, $entry['tseg1'], 1e-9);
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
        self::assertSame(3072, $result['segmentOffset']);
        self::assertSame(3114, $result['nextOffset']);
        self::assertSame([[3, 0, 0, 0], [3, 0, 0, 0], [3, 0, 0, 0]], $result['coordinateSizes']);

        self::assertCount(3, $result['coefficients']);
        self::assertCount(3, $result['coefficients'][0]);

        self::assertEqualsWithDelta(0.1, $result['coefficients'][0][0], 1e-15);
        self::assertEqualsWithDelta(0.01, $result['coefficients'][0][1], 1e-15);
        self::assertEqualsWithDelta(0.2, $result['coefficients'][1][0], 1e-15);
        self::assertEqualsWithDelta(0.03, $result['coefficients'][2][0], 1e-15);
    }

    public function testMoonSegmentCoefficientsCanBeDecoded(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(3072, $result['segmentOffset']);
        self::assertSame(3114, $result['nextOffset']);
        self::assertSame([[3, 0, 0, 0], [3, 0, 0, 0], [3, 0, 0, 0]], $result['coordinateSizes']);

        self::assertCount(3, $result['coefficients'][0]);

        self::assertEqualsWithDelta(0.004, $result['coefficients'][0][0], 1e-18);
        self::assertEqualsWithDelta(0.002, $result['coefficients'][0][1], 1e-18);
        self::assertEqualsWithDelta(0.006, $result['coefficients'][1][0], 1e-18);
        self::assertEqualsWithDelta(0.008, $result['coefficients'][2][0], 1e-18);
    }

    public function testExtendedSegmentCoefficientHeaderCanBeDecoded(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::segmentCoefficients($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(3072, $result['segmentOffset']);
        self::assertSame(3120, $result['nextOffset']);
        self::assertSame([[3, 0, 0, 0, 0, 0], [3, 0, 0, 0, 0, 0], [3, 0, 0, 0, 0, 0]], $result['coordinateSizes']);

        self::assertCount(3, $result['coefficients'][0]);

        self::assertEqualsWithDelta(1.0, $result['coefficients'][0][0], 1e-12);
        self::assertEqualsWithDelta(0.0, $result['coefficients'][0][1], 1e-12);
        self::assertEqualsWithDelta(2.0, $result['coefficients'][1][0], 1e-12);
        self::assertEqualsWithDelta(3.0, $result['coefficients'][2][0], 1e-12);
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
        self::assertSame(0, $result['segment']);
        self::assertEqualsWithDelta(0.0, $result['t'], 1e-15);

        self::assertEqualsWithDelta(0.049, $result['vector'][0], 1e-15);
        self::assertEqualsWithDelta(0.1, $result['vector'][1], 1e-15);
        self::assertEqualsWithDelta(0.015, $result['vector'][2], 1e-15);
        self::assertEqualsWithDelta(0.002, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(-0.004, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-15);
    }

    public function testMoonRawSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(0, $result['segment']);
        self::assertEqualsWithDelta(0.0, $result['t'], 1e-15);

        self::assertEqualsWithDelta(0.002, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(0.003, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(0.004, $result['vector'][2], 1e-18);
        self::assertEqualsWithDelta(0.0004, $result['vector'][3], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][4], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-18);
    }

    public function testRawSegmentVectorCanSkipSpeed(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::rawSegmentVector($resolved['path'], Catalog::SE_MERCURY, 2451545.0, false);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertEqualsWithDelta(0.049, $result['vector'][0], 1e-18);
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
        self::assertSame(0, $result['segment']);
        self::assertEqualsWithDelta(0.0, $result['t'], 1e-15);

        self::assertEqualsWithDelta(0.5, $result['vector'][0], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['vector'][1], 1e-12);
        self::assertEqualsWithDelta(1.5, $result['vector'][2], 1e-12);
        self::assertEqualsWithDelta(0.0, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-15);
    }

    public function testMercuryReferenceEllipseSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::referenceEllipseSegmentVector($resolved['path'], Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertTrue($result['referenceEllipseApplied']);
        self::assertEqualsWithDelta(0.0, $result['omtild'], 1e-13);

        self::assertEqualsWithDelta(0.05, $result['vector'][0], 1e-15);
        self::assertEqualsWithDelta(0.102, $result['vector'][1], 1e-15);
        self::assertEqualsWithDelta(0.015, $result['vector'][2], 1e-15);
        self::assertEqualsWithDelta(0.002, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(-0.004, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-20);
    }

    public function testMoonReferenceEllipseSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::referenceEllipseSegmentVector($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertTrue($result['referenceEllipseApplied']);
        self::assertEqualsWithDelta(0.0, $result['omtild'], 1e-13);

        self::assertEqualsWithDelta(0.002, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(0.003, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(0.004, $result['vector'][2], 1e-18);
        self::assertEqualsWithDelta(0.0004, $result['vector'][3], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][4], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-19);
    }

    public function testReferenceEllipseIsSkippedWhenDescriptorDoesNotUseIt(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::referenceEllipseSegmentVector($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertFalse($result['referenceEllipseApplied']);

        self::assertEqualsWithDelta(0.5, $result['vector'][0], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['vector'][1], 1e-12);
        self::assertEqualsWithDelta(1.5, $result['vector'][2], 1e-12);
    }

    public function testMercuryRotatedSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_PLANET, 2451545.0);
        $result = EphemerisFiles::rotatedSegmentVector($resolved['path'], Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertTrue($result['referenceEllipseApplied']);
        self::assertSame(2, $result['neval']);
        self::assertSame(3, $result['nEvaluate']);
        self::assertEqualsWithDelta(0.0, $result['qav'], 1e-14);
        self::assertEqualsWithDelta(0.0, $result['pav'], 1e-14);

        self::assertEqualsWithDelta(0.05, $result['vector'][0], 1e-15);
        self::assertEqualsWithDelta(0.102, $result['vector'][1], 1e-15);
        self::assertEqualsWithDelta(0.015, $result['vector'][2], 1e-15);
        self::assertEqualsWithDelta(0.002, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(-0.004, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-15);
    }

    public function testMoonRotatedSegmentVectorCanBeEvaluated(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MOON, 2451545.0);
        $result = EphemerisFiles::rotatedSegmentVector($resolved['path'], Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertTrue($result['referenceEllipseApplied']);
        self::assertSame(1, $result['neval']);
        self::assertSame(2, $result['nEvaluate']);
        self::assertEqualsWithDelta(0.0, $result['qav'], 1e-14);
        self::assertEqualsWithDelta(0.0, $result['pav'], 1e-14);

        self::assertEqualsWithDelta(0.002, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(0.0011613375635611345, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(0.00486325971581427, $result['vector'][2], 1e-18);
        self::assertEqualsWithDelta(0.0004, $result['vector'][3], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][4], 1e-18);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-18);
    }

    public function testRotatedSegmentVectorKeepsUnrotatedAsteroidWhenFlagsDoNotRequestRotation(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $resolved = EphemerisFiles::resolve(EphemerisFiles::TYPE_MAIN_ASTEROID, 2451545.0);
        $result = EphemerisFiles::rotatedSegmentVector($resolved['path'], Catalog::SE_MEAN_APOG, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertFalse($result['referenceEllipseApplied']);
        self::assertEqualsWithDelta(0.0, $result['qav'], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['pav'], 1e-15);

        self::assertEqualsWithDelta(0.5, $result['vector'][0], 1e-12);
        self::assertEqualsWithDelta(1.0, $result['vector'][1], 1e-12);
        self::assertEqualsWithDelta(1.5, $result['vector'][2], 1e-12);
    }

    public function testPositionReturnsMercuryVectorFromEphemerisFile(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::position(Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_MERCURY, $result['body']);
        self::assertSame(Catalog::SE_MERCURY, $result['ipl']);
        self::assertSame(EphemerisFiles::TYPE_PLANET, $result['type']);
        self::assertSame('sepl_18.se1', $result['file']);
        self::assertSame(0, $result['segment']);

        self::assertEqualsWithDelta(0.05, $result['vector'][0], 1e-15);
        self::assertEqualsWithDelta(0.102, $result['vector'][1], 1e-15);
        self::assertEqualsWithDelta(0.015, $result['vector'][2], 1e-15);
        self::assertEqualsWithDelta(0.002, $result['vector'][3], 1e-15);
        self::assertEqualsWithDelta(-0.004, $result['vector'][4], 1e-15);
        self::assertEqualsWithDelta(0.0, $result['vector'][5], 1e-15);
    }

    public function testPositionReturnsMoonVectorFromEphemerisFile(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::position(Catalog::SE_MOON, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_MOON, $result['body']);
        self::assertSame(Catalog::SE_MOON, $result['ipl']);
        self::assertSame(EphemerisFiles::TYPE_MOON, $result['type']);
        self::assertSame('semo_18.se1', $result['file']);

        self::assertEqualsWithDelta(0.002, $result['vector'][0], 1e-18);
        self::assertEqualsWithDelta(0.0011613375635611345, $result['vector'][1], 1e-18);
        self::assertEqualsWithDelta(0.00486325971581427, $result['vector'][2], 1e-18);
        self::assertEqualsWithDelta(0.0004, $result['vector'][3], 1e-18);
    }

    public function testPositionMapsMainAsteroidBodyToFileBodyNumber(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::position(Catalog::SE_AST_OFFSET + 1, 2451545.0);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertSame(Catalog::SE_AST_OFFSET + 1, $result['body']);
        self::assertSame(Catalog::SE_MEAN_APOG, $result['ipl']);
        self::assertSame(EphemerisFiles::TYPE_ASTEROID, $result['type']);
        self::assertSame('seas_18.se1', $result['file']);
    }

    public function testPositionCanSkipSpeed(): void
    {
        EphemerisFiles::setPath($this->ephePath());

        $result = EphemerisFiles::position(Catalog::SE_MERCURY, 2451545.0, false);

        self::assertSame(Catalog::SE_OK, $result['rc']);
        self::assertEqualsWithDelta(0.05, $result['vector'][0], 1e-15);
        self::assertSame(0.0, $result['vector'][3]);
        self::assertSame(0.0, $result['vector'][4]);
        self::assertSame(0.0, $result['vector'][5]);
    }

    public function testPositionReturnsErrorWhenFileIsMissing(): void
    {
        EphemerisFiles::setPath('/tmp/swissphp-missing-ephe-path');

        $result = EphemerisFiles::position(Catalog::SE_MERCURY, 2451545.0);

        self::assertSame(Catalog::SE_ERR, $result['rc']);
        self::assertSame('ephemeris file not found', $result['error']);
        self::assertSame([], $result['vector']);
    }

    private function ephePath(): string
    {
        return EphemerisFixtureFactory::path();
    }
}