<?php

declare(strict_types=1);

namespace SwissEph\Tests\Support;

use SwissEph\Catalog;
use SwissEph\EphemerisFiles;

final class EphemerisFixtureFactory
{
    private const TFSTART = 2451540.0;
    private const TFEND = 2451550.0;
    private const DSEG = 10.0;
    private const INDEX_OFFSET = 2048;
    private const SEGMENT_OFFSET = 3072;

    public static function path(): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'swissphp-ephe-fixtures';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        self::writeFile(
            $path . DIRECTORY_SEPARATOR . 'sepl_18.se1',
            'sepl_18.se1',
            Catalog::SE_MERCURY,
            EphemerisFiles::BODY_FLAG_HELIO
            | EphemerisFiles::BODY_FLAG_ROTATE
            | EphemerisFiles::BODY_FLAG_ELLIPSE
            | EphemerisFiles::BODY_FLAG_EMBHEL,
            1000.0,
            [
                [0.1, 0.01, 0.001],
                [0.2, -0.02, 0.0],
                [0.03, 0.0, 0.0],
            ],
            [
                0.002, 0.0, 0.0,
                0.004, 0.0, 0.0,
            ],
        );

        self::writeFile(
            $path . DIRECTORY_SEPARATOR . 'semo_18.se1',
            'semo_18.se1',
            Catalog::SE_MOON,
            EphemerisFiles::BODY_FLAG_ROTATE
            | EphemerisFiles::BODY_FLAG_ELLIPSE
            | EphemerisFiles::BODY_FLAG_EMBHEL,
            1.0,
            [
                [0.004, 0.002, 0.0],
                [0.006, 0.0, 0.0],
                [0.008, 0.0, 0.0],
            ],
            [
                0.0, 0.0, 0.0,
                0.0, 0.0, 0.0,
            ],
        );

        self::writeFile(
            $path . DIRECTORY_SEPARATOR . 'seas_18.se1',
            'seas_18.se1',
            Catalog::SE_MEAN_APOG,
            EphemerisFiles::BODY_FLAG_EMBHEL,
            10.0,
            [
                [1.0, 0.0, 0.0],
                [2.0, 0.0, 0.0],
                [3.0, 0.0, 0.0],
            ],
            [],
            true,
        );

        return $path;
    }

    /**
     * @param array{0:list<float>, 1:list<float>, 2:list<float>} $coefficients
     * @param list<float> $refep
     */
    private static function writeFile(
        string $path,
        string $fileName,
        int    $ipl,
        int    $flags,
        float  $rmax,
        array  $coefficients,
        array  $refep,
        bool   $extendedCoefficientHeader = false,
    ): void
    {
        $ncoe = count($coefficients[0]);

        $header = "SWISSEPH  1\r\n"
            . $fileName . " \r\n"
            . "Synthetic SwissPHP test ephemeris fixture.\r\n"
            . "cba\0";

        $bytes = $header;
        $lengthOffset = strlen($bytes);

        $bytes .= pack('V', 0);
        $bytes .= pack('V', 431);
        $bytes .= pack('e', self::TFSTART);
        $bytes .= pack('e', self::TFEND);
        $bytes .= pack('v', 1);
        $bytes .= pack('v', $ipl);
        $bytes .= pack('V', 0);
        $bytes .= str_repeat("\0", 40);

        $bytes .= pack('V', self::INDEX_OFFSET);
        $bytes .= pack('C', $flags);
        $bytes .= pack('C', $ncoe);
        $bytes .= pack('V', (int)round($rmax * 1000.0));

        foreach ([
                     self::TFSTART,
                     self::TFEND,
                     self::DSEG,
                     2451545.0,
                     0.0,
                     0.0,
                     0.0,
                     0.0,
                     0.0,
                     0.0,
                 ] as $value) {
            $bytes .= pack('e', $value);
        }

        foreach ($refep as $value) {
            $bytes .= pack('e', $value);
        }

        $bytes = str_pad($bytes, self::INDEX_OFFSET, "\0");
        $bytes .= self::packUInt24(self::SEGMENT_OFFSET);

        $bytes = str_pad($bytes, self::SEGMENT_OFFSET, "\0");

        foreach ($coefficients as $coordinateCoefficients) {
            $bytes .= self::packCoordinate($coordinateCoefficients, $rmax, $extendedCoefficientHeader);
        }

        $bytes = substr_replace($bytes, pack('V', strlen($bytes)), $lengthOffset, 4);

        file_put_contents($path, $bytes);
    }

    /**
     * @param list<float> $coefficients
     */
    private static function packCoordinate(array $coefficients, float $rmax, bool $extended): string
    {
        $count = count($coefficients);

        if ($extended) {
            $bytes = pack('C4', 128, $count << 4, 0, 0);
        } else {
            $bytes = pack('C2', $count << 4, 0);
        }

        foreach ($coefficients as $coefficient) {
            $bytes .= pack('V', self::packCoefficientValue($coefficient, $rmax));
        }

        return $bytes;
    }

    private static function packCoefficientValue(float $coefficient, float $rmax): int
    {
        $scaled = (int)round(abs($coefficient) * 4_000_000_000 / $rmax);

        if ($coefficient < 0.0) {
            return $scaled % 2 === 0 ? $scaled - 1 : $scaled;
        }

        return $scaled % 2 === 0 ? $scaled : $scaled + 1;
    }

    private static function packUInt24(int $value): string
    {
        return chr($value & 0xff)
            . chr(($value >> 8) & 0xff)
            . chr(($value >> 16) & 0xff);
    }
}