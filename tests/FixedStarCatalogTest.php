<?php

declare(strict_types=1);

namespace SwissEph\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SwissEph\FixedStarCatalog;

final class FixedStarCatalogTest extends TestCase
{
    public function testCatalogCanFindCanonicalNameAndAliases(): void
    {
        $catalog = new FixedStarCatalog([
            [
                'name' => 'Sirius',
                'aliases' => ['alpha canis majoris', 'alf cmj', 'dog star'],
                'ra' => 101.28715533,
                'dec' => -16.71611586,
                'pmRa' => -546.01,
                'pmDec' => -1223.07,
                'parallax' => 379.21,
                'mag' => -1.46,
            ],
        ]);

        self::assertTrue($catalog->exists('Sirius'));
        self::assertTrue($catalog->exists('dog-star'));
        self::assertTrue($catalog->exists('alpha_canis_majoris'));
        self::assertFalse($catalog->exists('Unknown Star'));

        $star = $catalog->find('alf cmj');

        self::assertNotNull($star);
        self::assertSame('Sirius', $star['name']);
        self::assertSame(-1.46, $star['mag']);
    }

    public function testNamesAndAllReturnCatalogEntries(): void
    {
        $catalog = new FixedStarCatalog([
            [
                'name' => 'Sirius',
                'ra' => 101.28715533,
                'dec' => -16.71611586,
            ],
            [
                'name' => 'Spica',
                'ra' => 201.29824709,
                'dec' => -11.16132203,
            ],
        ]);

        self::assertSame(['Sirius', 'Spica'], $catalog->names());
        self::assertCount(2, $catalog->all());
        self::assertSame([], $catalog->all()[0]['aliases']);
        self::assertSame(0.0, $catalog->all()[0]['pmRa']);
        self::assertSame(0.0, $catalog->all()[0]['mag']);
    }

    public function testCatalogCanBeParsedFromString(): void
    {
        $catalog = FixedStarCatalog::fromString(
            <<<TXT
            # name|aliases|ra|dec|pmRa|pmDec|parallax|mag
            Sirius|alpha canis majoris,alf cmj,dog star|101.28715533|-16.71611586|-546.01|-1223.07|379.21|-1.46
            Spica|alpha virginis,alf vir|201.29824709|-11.16132203|-42.35|-31.73|13.06|0.98
            TXT
        );

        self::assertSame(['Sirius', 'Spica'], $catalog->names());

        $spica = $catalog->find('alf-vir');

        self::assertNotNull($spica);
        self::assertSame('Spica', $spica['name']);
        self::assertSame(201.29824709, $spica['ra']);
        self::assertSame(0.98, $spica['mag']);
    }

    public function testParseLineRejectsIncompleteLine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('fixed star catalog line must contain');

        FixedStarCatalog::parseLine('Sirius|dog star|101.28715533');
    }

    public function testNormalizeNameMatchesFixedStarLookupRules(): void
    {
        self::assertSame('alpha canis majoris', FixedStarCatalog::normalizeName(' alpha-canis_majoris '));
        self::assertSame('dog star', FixedStarCatalog::normalizeName('dog   star'));
    }

    public function testSwissEphemerisCsvLineCanBeParsed(): void
    {
        $star = FixedStarCatalog::parseLine(
            'Sirius,alpha Canis Majoris,2000,101.287155333,-16.716115861,-546.01,-1223.07,-5.5,379.21,-1.46'
        );

        self::assertSame('Sirius', $star['name']);
        self::assertSame(['alpha Canis Majoris'], $star['aliases']);
        self::assertSame(101.287155333, $star['ra']);
        self::assertSame(-16.716115861, $star['dec']);
        self::assertSame(-546.01, $star['pmRa']);
        self::assertSame(-1223.07, $star['pmDec']);
        self::assertSame(379.21, $star['parallax']);
        self::assertSame(-1.46, $star['mag']);
    }

    public function testSwissEphemerisCsvLineCanParseSexagesimalCoordinates(): void
    {
        $star = FixedStarCatalog::parseLine(
            'Sirius,alpha Canis Majoris,2000,06 46 08.91728,-16 42 58.0171,-546.01,-1223.07,-5.5,379.21,-1.46'
        );

        self::assertSame('Sirius', $star['name']);
        self::assertEqualsWithDelta(181.28715533333334, $star['ra'], 1e-12);
        self::assertEqualsWithDelta(-16.71611586111111, $star['dec'], 1e-12);
    }

    public function testSwissEphemerisCsvLineCanParseColonSeparatedCoordinates(): void
    {
        $star = FixedStarCatalog::parseLine(
            'Sirius,alpha Canis Majoris,2000,06:45:08.91728,-16:42:58.0171,-546.01,-1223.07,-5.5,379.21,-1.46'
        );

        self::assertEqualsWithDelta(101.28715533333334, $star['ra'], 1e-12);
        self::assertEqualsWithDelta(-16.17611586111111, $star['dec'], 1e-12);
    }

    public function testSwissEphemerisCsvLineExpandsAliasesFromNames(): void
    {
        $catalog = FixedStarCatalog::fromString(
            'Sirius;Dog Star,alpha Canis Majoris;Alpha CMa,2000,06 45 08.91728,-16 42 58.0171,-546.01,-1223.07,-5.5,379.21,-1.46'
        );

        self::assertTrue($catalog->exists('alpha canis majoris'));
        self::assertTrue($catalog->exists('Alpha CMa'));
        self::assertTrue($catalog->exists('Dog Star'));
        self::assertTrue($catalog->exists('dog-star'));

        $star = $catalog->find('alpha-cma');

        self::assertNotNull($star);
        self::assertSame('Sirius;Dog Star', $star['name']);
    }
}