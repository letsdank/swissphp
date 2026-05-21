<?php

declare(strict_types=1);

namespace SwissEph;

/**
 * Low-level Swiss Ephemeris `.se1` file reader.
 *
 * This class reads Swiss Ephemeris binary files directly: text header,
 * metadata, body descriptors, segment index entries, packed Chebyshev
 * coefficients, reference ellipse data, and rotated segment vectors.
 *
 * The API is intentionally lower-level than Calculator::calc(). It returns
 * vectors from the ephemeris files after reference ellipse handling and
 * rot_back() rotation, but it does not apply the complete Swiss Ephemeris
 * apparent/geocentric correction pipeline yet.
 *
 * Ephemeris data files are not bundled with SwissPHP. Call setPath() with a
 * directory containing `.se1` files before using resolve(), metadata(), or
 * position().
 */
final class EphemerisFiles
{
    public const TYPE_PLANET = 'planet';
    public const TYPE_MOON = 'moon';
    public const TYPE_MAIN_ASTEROID = 'main_asteroid';
    public const TYPE_ASTEROID = 'asteroid';

    private const MARKER_LITTLE_ENDIAN = "cba\0";
    private const MARKER_BIG_ENDIAN = "\0abc";

    public const BODY_FLAG_HELIO = 1;
    public const BODY_FLAG_ROTATE = 2;
    public const BODY_FLAG_ELLIPSE = 4;
    public const BODY_FLAG_EMBHEL = 8;

    private const CRC_BYTES = 4;
    private const GENERAL_CONSTANT_BYTES = 40;
    private const BODY_DESCRIPTOR_BYTES = 90;

    private const J2000_SIN_EPS = 0.39777715572793088;
    private const J2000_COS_EPS = 0.91748206215761929;

    private static string $path = '';

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function path(): string
    {
        return self::$path;
    }

    /**
     * @return array{rc:int, type:string, file:string, path:string, error:string}
     */
    public static function resolve(string $type, float $tjdEt): array
    {
        $prefix = self::prefix($type);

        if ($prefix === null) {
            return self::error($type, '', '', 'unsupported ephemeris file type');
        }

        if (self::$path === '') {
            return self::error($type, '', '', 'ephemeris path is not set');
        }

        $file = $prefix . self::segmentSuffix($tjdEt) . '.se1';
        $path = self::$path . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            return self::error($type, $file, $path, 'ephemeris file not found');
        }

        return [
            'rc' => Catalog::SE_OK,
            'type' => $type,
            'file' => $file,
            'path' => $path,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, type:string, file:string, path:string, metadata:array<string, mixed>|null, error:string}
     */
    public static function resolveWithMetadata(string $type, float $tjdEt): array
    {
        $resolved = self::resolve($type, $tjdEt);

        if ($resolved['rc'] !== Catalog::SE_OK) {
            return [
                'rc' => $resolved['rc'],
                'type' => $resolved['type'],
                'file' => $resolved['file'],
                'path' => $resolved['path'],
                'metadata' => null,
                'error' => $resolved['error'],
            ];
        }

        $metadata = self::metadata($resolved['path']);

        if ($metadata['rc'] !== Catalog::SE_OK) {
            return [
                'rc' => Catalog::SE_ERR,
                'type' => $resolved['type'],
                'file' => $resolved['file'],
                'path' => $resolved['path'],
                'metadata' => null,
                'error' => $resolved['error'],
            ];
        }

        if (!self::containsDate($metadata, $tjdEt)) {
            return [
                'rc' => Catalog::SE_ERR,
                'type' => $resolved['type'],
                'file' => $resolved['file'],
                'path' => $resolved['path'],
                'metadata' => $metadata,
                'error' => 'julian day is outside ephemeris file range',
            ];
        }

        return [
            'rc' => Catalog::SE_OK,
            'type' => $resolved['type'],
            'file' => $resolved['file'],
            'path' => $resolved['path'],
            'metadata' => $metadata,
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, body:int, type:string, file:string, path:string, error:string}
     */
    public static function resolveForBody(int $body, float $tjdEt): array
    {
        $type = self::typeForBody($body);
        $result = self::resolve($type, $tjdEt);

        return [
            'rc' => $result['rc'],
            'body' => $body,
            'type' => $result['type'],
            'file' => $result['file'],
            'path' => $result['path'],
            'error' => $result['error'],
        ];
    }

    /**
     * Evaluates a body directly from Swiss Ephemeris `.se1` files.
     *
     * This method is the highest-level API in this class. It resolves the file
     * for the requested body and date, reads the body descriptor, decodes the
     * matching Chebyshev segment, applies the reference ellipse when present,
     * applies Swiss Ephemeris-style rot_back() rotation, and returns a six-value
     * vector `[x, y, z, dx, dy, dz]`.
     *
     * The returned vector is still a low-level ephemeris-file vector. It is not
     * the final result of swe_calc()/Calculator::calc() because light-time,
     * aberration, deflection, precession/nutation, topocentric, sidereal, and
     * output-format transformations are handled elsewhere.
     *
     * @return array<string, mixed>
     */
    public static function position(int $body, float $tjdEt, bool $withSpeed = true): array
    {
        $resolved = self::resolveForBody($body, $tjdEt);

        if ($resolved['rc'] !== Catalog::SE_OK) {
            return self::positionError($body, $resolved['type'], $resolved['file'], $resolved['path'], $resolved['error']);
        }

        $metadata = self::metadata($resolved['path']);

        if ($metadata['rc'] !== Catalog::SE_OK) {
            return self::positionError($body, $resolved['type'], $resolved['file'], $resolved['path'], $metadata['error']);
        }

        if (!self::containsDate($metadata, $tjdEt)) {
            return self::positionError(
                $body,
                $resolved['type'],
                $resolved['file'],
                $resolved['path'],
                'julian day is outside ephemeris file range'
            );
        }

        $ipl = self::fileBodyNumber($body);
        $vector = self::rotatedSegmentVector($resolved['path'], $ipl, $tjdEt, $withSpeed);

        if ($vector['rc'] !== Catalog::SE_OK) {
            return self::positionError($body, $resolved['type'], $resolved['file'], $resolved['path'], $vector['error']);
        }

        return [
            'rc' => Catalog::SE_OK,
            'body' => $body,
            'ipl' => $ipl,
            'type' => $resolved['type'],
            'file' => $resolved['file'],
            'path' => $resolved['path'],
            'segment' => $vector['segment'],
            'position' => $vector['position'],
            'speed' => $vector['speed'],
            'vector' => $vector['vector'],
            'metadata' => $metadata,
            'descriptor' => $vector['descriptor'],
            'error' => '',
        ];
    }

    /**
     * @return array{rc:int, path:string, file:string, version:string, fileLine:string, copyright:string, raw:string, error:string}
     */
    public static function header(string $path): array
    {
        if (!is_file($path)) {
            return [
                'rc' => Catalog::SE_ERR,
                'path' => $path,
                'file' => basename($path),
                'version' => '',
                'fileLine' => '',
                'copyright' => '',
                'raw' => '',
                'error' => 'ephemeris file not found',
            ];
        }

        $raw = file_get_contents($path, false, null, 0, 512);

        if ($raw === false) {
            return [
                'rc' => Catalog::SE_ERR,
                'path' => $path,
                'file' => basename($path),
                'version' => '',
                'fileLine' => '',
                'copyright' => '',
                'raw' => '',
                'error' => 'cannot read ephemeris file',
            ];
        }

        $lines = preg_split('/\r\n|\n|\r/', $raw) ?: [];

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'version' => trim($lines[0] ?? ''),
            'fileLine' => trim($lines[1] ?? ''),
            'copyright' => trim($lines[2] ?? ''),
            'raw' => $raw,
            'error' => '',
        ];
    }

    /**
     * @return array{
     *     rc:int,
     *     path:string,
     *     file:string,
     *     endian:string,
     *     markerOffset:int,
     *     dataOffset:int,
     *     planetDataOffset:int,
     *     fileLength:int,
     *     actualFileLength:int,
     *     denum:int,
     *     tfstart:float,
     *     tfend:float,
     *     nplan:int,
     *     ipl:list<int>,
     *     error:string
     * }
     */
    public static function metadata(string $path): array
    {
        if (!is_file($path)) {
            return self::metadataError($path, 'ephemeris file not found');
        }

        $actualFileLength = filesize($path);

        if ($actualFileLength === false) {
            return self::metadataError($path, 'cannot stat ephemeris file');
        }

        $head = file_get_contents($path, false, null, 0, 2048);

        if ($head === false) {
            return self::metadataError($path, 'cannot read ephemeris file');
        }

        $marker = self::findEndianMarker($head);

        if ($marker === null) {
            return self::metadataError($path, 'ephemeris endian marker not found');
        }

        $offset = $marker['offset'] + 4;
        $endian = $marker['endian'];

        $fixed = self::readAt($path, $offset, 4 + 4 + 8 + 8 + 2);

        if ($fixed === null) {
            return self::metadataError($path, 'cannot read ephemeris metadata block');
        }

        $cursor = 0;
        $fileLength = self::readUInt32(substr($fixed, $cursor, 4), $endian);
        $cursor += 4;

        $denum = self::readUInt32(substr($fixed, $cursor, 4), $endian);
        $cursor += 4;

        $tfstart = self::readDouble(substr($fixed, $cursor, 8), $endian);
        $cursor += 8;

        $tfend = self::readDouble(substr($fixed, $cursor, 8), $endian);
        $cursor += 8;

        $nplanRaw = self::readUInt16(substr($fixed, $cursor, 2), $endian);
        $nplan = $nplanRaw;
        $iplBytes = 2;

        if ($nplan > 256) {
            $iplBytes = 4;
            $nplan %= 256;
        }

        if ($nplan < 1 || $nplan > 20) {
            return self::metadataError($path, 'invalid planet count in ephemeris metadata');
        }

        if ($fileLength !== $actualFileLength) {
            return self::metadataError($path, 'ephemeris file length does not match metadata');
        }

        $planetListOffset = $offset + 4 + 4 + 8 + 8 + 2;
        $planetListBytes = self::readAt($path, $planetListOffset, $nplan * $iplBytes);

        if ($planetListBytes === null) {
            return self::metadataError($path, 'cannot read ephemeris planet list');
        }

        $ipl = [];

        for ($i = 0; $i < $nplan; $i++) {
            $bytes = substr($planetListBytes, $i * $iplBytes, $iplBytes);
            $ipl[] = $iplBytes === 2
                ? self::readUInt16($bytes, $endian)
                : self::readUInt32($bytes, $endian);
        }

        $crcOffset = $planetListOffset + ($nplan * $iplBytes);
        $generalConstantsOffset = $crcOffset + self::CRC_BYTES;
        $bodyDescriptorsOffset = $generalConstantsOffset + self::GENERAL_CONSTANT_BYTES;

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'endian' => $endian,
            'markerOffset' => $marker['offset'],
            'dataOffset' => $offset,
            'crcOffset' => $crcOffset,
            'generalConstantsOffset' => $generalConstantsOffset,
            'bodyDescriptorsOffset' => $bodyDescriptorsOffset,
            'planetDataOffset' => $bodyDescriptorsOffset,
            'fileLength' => $fileLength,
            'actualFileLength' => $actualFileLength,
            'denum' => $denum,
            'tfstart' => $tfstart,
            'tfend' => $tfend,
            'nplan' => $nplan,
            'ipl' => $ipl,
            'error' => '',
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function containsDate(array $metadata, float $tjdEt): bool
    {
        return $tjdEt >= (float)$metadata['tfstart'] && $tjdEt <= (float)$metadata['tfend'];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function containsPlanet(array $metadata, int $ipl): bool
    {
        return in_array($ipl, $metadata['ipl'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function bodyDescriptors(string $path): array
    {
        $metadata = self::metadata($path);

        if ($metadata['rc'] !== Catalog::SE_OK) {
            return self::descriptorError($path, $metadata['error']);
        }

        $offset = (int)$metadata['bodyDescriptorsOffset'];
        $endian = (string)$metadata['endian'];
        $descriptors = [];

        foreach ($metadata['ipl'] as $ipl) {
            $bytes = self::readAt($path, $offset, self::BODY_DESCRIPTOR_BYTES);

            if ($bytes === null) {
                return self::descriptorError($path, 'cannot read ephemeris body descriptor');
            }

            $cursor = 0;

            $lndx0 = self::readUInt32(substr($bytes, $cursor, 4), $endian);
            $cursor += 4;

            $flags = self::readUInt8(substr($bytes, $cursor, 1));
            $cursor += 1;

            $ncoe = self::readUInt8(substr($bytes, $cursor, 1));
            $cursor += 1;

            $rmaxRaw = self::readUInt32(substr($bytes, $cursor, 4), $endian);
            $cursor += 4;

            $values = [];

            for ($i = 0; $i < 10; $i++) {
                $values[] = self::readDouble(substr($bytes, $cursor, 8), $endian);
                $cursor += 8;
            }

            $nextOffset = $offset + self::BODY_DESCRIPTOR_BYTES;
            $refep = [];
            $refepOffset = null;
            $refepCount = 0;

            if (($flags & self::BODY_FLAG_ELLIPSE) !== 0) {
                $refepOffset = $nextOffset;
                $refepCount = $ncoe * 2;
                $refep = self::readDoubleList($path, $refepOffset, $refepCount, $endian);

                if ($refep === null) {
                    return self::descriptorError($path, 'cannot read ephemeris reference ellipse');
                }

                $nextOffset += $refepCount * 8;
            }

            $tfstart = $values[0];
            $tfend = $values[1];
            $dseg = $values[2];

            $descriptors[] = [
                'ipl' => $ipl,
                'offset' => $offset,
                'lndx0' => $lndx0,
                'flags' => $flags,
                'isHeliocentric' => ($flags & self::BODY_FLAG_HELIO) !== 0,
                'isRotated' => ($flags & self::BODY_FLAG_ROTATE) !== 0,
                'usesReferenceEllipse' => ($flags & self::BODY_FLAG_ELLIPSE) !== 0,
                'isEmbHeliocentric' => ($flags & self::BODY_FLAG_EMBHEL) !== 0,
                'ncoe' => $ncoe,
                'rmax' => $rmaxRaw / 1000.0,
                'tfstart' => $tfstart,
                'tfend' => $tfend,
                'dseg' => $dseg,
                'nndx' => (int)(($tfend - $tfstart + 0.1) / $dseg),
                'telem' => $values[3],
                'prot' => $values[4],
                'dprot' => $values[5],
                'qrot' => $values[6],
                'dqrot' => $values[7],
                'peri' => $values[8],
                'dperi' => $values[9],
                'refepOffset' => $refepOffset,
                'refepCount' => $refepCount,
                'refep' => $refep,
                'nextOffset' => $nextOffset,
            ];

            $offset = $nextOffset;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'metadata' => $metadata,
            'descriptors' => $descriptors,
            'error' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function bodyDescriptor(string $path, int $ipl): array
    {
        $result = self::bodyDescriptors($path);

        if ($result['rc'] !== Catalog::SE_OK) {
            return $result;
        }

        foreach ($result['descriptors'] as $descriptor) {
            if ($descriptor['ipl'] === $ipl) {
                return [
                    'rc' => Catalog::SE_OK,
                    'path' => $path,
                    'file' => basename($path),
                    'descriptor' => $descriptor,
                    'error' => '',
                ];
            }
        }

        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'descriptor' => null,
            'error' => 'ephemeris body descriptor not found',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function segmentIndexEntry(string $path, int $ipl, float $tjdEt): array
    {
        $descriptors = self::bodyDescriptors($path);

        if ($descriptors['rc'] !== Catalog::SE_OK) {
            return self::segmentIndexError($path, $ipl, $descriptors['error']);
        }

        $metadata = $descriptors['metadata'];
        $descriptor = null;

        foreach ($descriptors['descriptors'] as $item) {
            if ($item['ipl'] === $ipl) {
                $descriptor = $item;
                break;
            }
        }

        if ($descriptor === null) {
            return self::segmentIndexError($path, $ipl, 'ephemeris body descriptor not found');
        }

        $tfstart = (float)$descriptor['tfstart'];
        $tfend = (float)$descriptor['tfend'];
        $dseg = (float)$descriptor['dseg'];

        if ($tjdEt < $tfstart || $tjdEt >= $tfend) {
            return self::segmentIndexError($path, $ipl, 'julian day is outside ephemeris body range');
        }

        $segment = (int)floor(($tjdEt - $tfstart) / $dseg);
        $nndx = (int)$descriptor['nndx'];

        if ($segment < 0 || $segment >= $nndx) {
            return self::segmentIndexError($path, $ipl, 'ephemeris segment index is outside body range');
        }

        $indexOffset = (int)$descriptor['lndx0'] + ($segment * 3);
        $bytes = self::readAt($path, $indexOffset, 3);

        if ($bytes === null) {
            return self::segmentIndexError($path, $ipl, 'cannot read ephemeris segment index entry');
        }

        $segmentOffset = self::readUInt24($bytes, (string)$metadata['endian']);

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $segment,
            'indexOffset' => $indexOffset,
            'segmentOffset' => $segmentOffset,
            'tseg0' => $tfstart + ($segment * $dseg),
            'tseg1' => $tfstart + (($segment + 1) * $dseg),
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * Decodes packed Chebyshev coefficients for the segment containing $tjdEt.
     *
     * Swiss `.se1` files store each coordinate with a compact variable-width
     * integer packing. This method performs only the binary unpacking step. The
     * returned coefficients are still in the file's local segment frame and may
     * still need reference ellipse handling and rot_back() rotation.
     *
     * @return array<string, mixed>
     */
    public static function segmentCoefficients(string $path, int $ipl, float $tjdEt): array
    {
        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::segmentCoefficientsError($path, $ipl, $entry['error']);
        }

        $metadata = self::metadata($path);

        if ($metadata['rc'] !== Catalog::SE_OK) {
            return self::segmentCoefficientsError($path, $ipl, $metadata['error']);
        }

        $descriptor = $entry['descriptor'];
        $offset = (int)$entry['segmentOffset'];
        $endian = (string)$metadata['endian'];
        $ncoe = (int)$descriptor['ncoe'];
        $rmax = (float)$descriptor['rmax'];

        $coefficients = [];
        $coordinateSizes = [];

        for ($coord = 0; $coord < 3; $coord++) {
            $decoded = self::decodeCoordinateCoefficients($path, $offset, $ncoe, $rmax, $endian);

            if ($decoded['rc'] !== Catalog::SE_OK) {
                return self::segmentCoefficientsError($path, $ipl, $decoded['error']);
            }

            $coefficients[] = $decoded['coefficients'];
            $coordinateSizes[] = $decoded['sizes'];
            $offset = (int)$decoded['nextOffset'];
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $entry['segment'],
            'segmentOffset' => $entry['segmentOffset'],
            'nextOffset' => $offset,
            'coordinateSizes' => $coordinateSizes,
            'coefficients' => $coefficients,
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function segmentCoefficientsWithReferenceEllipse(string $path, int $ipl, float $tjdEt): array
    {
        $segment = self::segmentCoefficients($path, $ipl, $tjdEt);

        if ($segment['rc'] !== Catalog::SE_OK) {
            return self::referenceEllipseCoefficientsError($path, $ipl, $segment['error']);
        }

        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::referenceEllipseCoefficientsError($path, $ipl, $entry['error']);
        }

        $descriptor = $segment['descriptor'];
        $coefficients = $segment['coefficients'];

        $omtild = 0.0;
        $com = 1.0;
        $som = 0.0;
        $applied = false;

        if ((bool)$descriptor['usesReferenceEllipse']) {
            $ncoe = (int)$descriptor['ncoe'];
            $refep = $descriptor['refep'];

            $tdiff = (((float)$entry['tseg0'] + (float)$descriptor['dseg'] / 2.0) - (float)$descriptor['telem']) / 365250.0;
            $omtild = self::normalizeRadians((float)$descriptor['peri'] + $tdiff * (float)$descriptor['dperi']);
            $com = cos($omtild);
            $som = sin($omtild);

            $refepx = array_slice($refep, 0, $ncoe);
            $refepy = array_slice($refep, $ncoe, $ncoe);

            for ($i = 0; $i < $ncoe; $i++) {
                $x = $coefficients[0][$i];
                $y = $coefficients[1][$i];

                $coefficients[0][$i] = $x + $com * $refepx[$i] - $som * $refepy[$i];
                $coefficients[1][$i] = $y + $com * $refepy[$i] + $som * $refepx[$i];
            }

            $applied = true;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $segment['segment'],
            'segmentOffset' => $segment['segmentOffset'],
            'nextOffset' => $segment['nextOffset'],
            'coordinateSizes' => $segment['coordinateSizes'],
            'coefficients' => $coefficients,
            'referenceEllipseApplied' => $applied,
            'omtild' => $omtild,
            'cosOmtild' => $com,
            'sinOmtild' => $som,
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * Evaluates a segment after adding the reference ellipse, before rot_back().
     *
     * Bodies with BODY_FLAG_ELLIPSE store Chebyshev residuals around a reference
     * orbit. This method adds the reference ellipse and evaluates the segment,
     * but it intentionally stops before applying the orientation rotation.
     *
     * @return array<string, mixed>
     */
    public static function referenceEllipseSegmentVector(string $path, int $ipl, float $tjdEt, bool $withSpeed = true): array
    {
        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::referenceEllipseVectorError($path, $ipl, $entry['error']);
        }

        $segment = self::segmentCoefficientsWithReferenceEllipse($path, $ipl, $tjdEt);

        if ($segment['rc'] !== Catalog::SE_OK) {
            return self::referenceEllipseVectorError($path, $ipl, $segment['error']);
        }

        $descriptor = $segment['descriptor'];
        $ncoe = (int)$descriptor['ncoe'];
        $dseg = (float)$descriptor['dseg'];
        $t = (($tjdEt - (float)$entry['tseg0']) / $dseg) * 2.0 - 1.0;

        $position = [];
        $speed = [];

        for ($coord = 0; $coord < 3; $coord++) {
            $coefficients = $segment['coefficients'][$coord];

            $position[] = self::evaluateChebyshev($t, $coefficients, $ncoe);
            $speed[] = $withSpeed
                ? self::evaluateChebyshevDerivative($t, $coefficients, $ncoe) / $dseg * 2.0
                : 0.0;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $entry['segment'],
            't' => $t,
            'position' => $position,
            'speed' => $speed,
            'vector' => [
                $position[0],
                $position[1],
                $position[2],
                $speed[0],
                $speed[1],
                $speed[2],
            ],
            'referenceEllipseApplied' => $segment['referenceEllipseApplied'],
            'omtild' => $segment['omtild'],
            'cosOmtild' => $segment['cosOmtild'],
            'sinOmtild' => $segment['sinOmtild'],
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * Applies Swiss Ephemeris rot_back() orientation to segment coefficients.
     *
     * @return array<string, mixed>
     */
    public static function rotatedSegmentCoefficients(string $path, int $ipl, float $tjdEt): array
    {
        $segment = self::segmentCoefficientsWithReferenceEllipse($path, $ipl, $tjdEt);

        if ($segment['rc'] !== Catalog::SE_OK) {
            return self::rotatedCoefficientsError($path, $ipl, $segment['error']);
        }

        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::rotatedCoefficientsError($path, $ipl, $entry['error']);
        }

        $descriptor = $segment['descriptor'];
        $coefficients = $segment['coefficients'];
        $ncoe = (int)$descriptor['ncoe'];

        $tdiff = (((float)$entry['tseg0'] + (float)$descriptor['dseg'] / 2.0) - (float)$descriptor['telem']) / 365250.0;

        if ($ipl === Catalog::SE_MOON) {
            $dn = self::normalizeRadians((float)$descriptor['prot'] + $tdiff * (float)$descriptor['dprot']);
            $qrot = (float)$descriptor['qrot'] + $tdiff * (float)$descriptor['dqrot'];
            $qav = $qrot * cos($dn);
            $pav = $qrot * sin($dn);
        } else {
            $qav = (float)$descriptor['qrot'] + $tdiff * (float)$descriptor['dqrot'];
            $pav = (float)$descriptor['prot'] + $tdiff * (float)$descriptor['dprot'];
        }

        $cosih2 = 1.0 / (1.0 + $qav * $qav + $pav * $pav);

        $uiz = [
            2.0 * $pav * $cosih2,
            -2.0 * $qav * $cosih2,
            (1.0 - $qav * $qav - $pav * $pav) * $cosih2,
        ];

        $uix = [
            (1.0 + $qav * $qav - $pav * $pav) * $cosih2,
            2.0 * $qav * $pav * $cosih2,
            -2.0 * $pav * $cosih2,
        ];

        $uiy = [
            2.0 * $qav * $pav * $cosih2,
            (1.0 - $qav * $qav + $pav * $pav) * $cosih2,
            2.0 * $qav * $cosih2,
        ];

        $neval = 0;

        for ($i = 0; $i < $ncoe; $i++) {
            $x = $coefficients[0][$i];
            $y = $coefficients[1][$i];
            $z = $coefficients[2][$i];

            $xrot = $x * $uix[0] + $y * $uiy[0] + $z * $uiz[0];
            $yrot = $x * $uix[1] + $y * $uiy[1] + $z * $uiz[1];
            $zrot = $x * $uix[2] + $y * $uiy[2] + $z * $uiz[2];

            if (abs($xrot) + abs($yrot) + abs($zrot) >= 1e-14) {
                $neval = $i;
            }

            if ($ipl === Catalog::SE_MOON) {
                $equatorialY = self::J2000_COS_EPS * $yrot - self::J2000_SIN_EPS * $zrot;
                $equatorialZ = self::J2000_SIN_EPS * $yrot + self::J2000_COS_EPS * $zrot;

                $yrot = $equatorialY;
                $zrot = $equatorialZ;
            }

            $coefficients[0][$i] = $xrot;
            $coefficients[1][$i] = $yrot;
            $coefficients[2][$i] = $zrot;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $segment['segment'],
            'segmentOffset' => $segment['segmentOffset'],
            'nextOffset' => $segment['nextOffset'],
            'coordinateSizes' => $segment['coordinateSizes'],
            'coefficients' => $coefficients,
            'referenceEllipseApplied' => $segment['referenceEllipseApplied'],
            'qav' => $qav,
            'pav' => $pav,
            'uix' => $uix,
            'uiy' => $uiy,
            'uiz' => $uiz,
            'neval' => $neval,
            'nEvaluate' => $neval + 1,
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * Evaluates a segment after reference ellipse handling and rot_back().
     *
     * This mirrors the low-level Swiss Ephemeris file step after Chebyshev
     * decoding: residuals are combined with the reference ellipse, coefficients
     * are rotated into their final file-frame orientation, and the segment is
     * evaluated at $tjdEt.
     *
     * @return array<string, mixed>
     */
    public static function rotatedSegmentVector(string $path, int $ipl, float $tjdEt, bool $withSpeed = true): array
    {
        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::rotatedVectorError($path, $ipl, $entry['error']);
        }

        $segment = self::rotatedSegmentCoefficients($path, $ipl, $tjdEt);

        if ($segment['rc'] !== Catalog::SE_OK) {
            return self::rotatedVectorError($path, $ipl, $segment['error']);
        }

        $descriptor = $segment['descriptor'];
        $ncoe = (int)$segment['nEvaluate'];
        $dseg = (float)$descriptor['dseg'];
        $t = (($tjdEt - (float)$entry['tseg0']) / $dseg) * 2.0 - 1.0;

        $position = [];
        $speed = [];

        for ($coord = 0; $coord < 3; $coord++) {
            $coefficients = $segment['coefficients'][$coord];

            $position[] = self::evaluateChebyshev($t, $coefficients, $ncoe);
            $speed[] = $withSpeed
                ? self::evaluateChebyshevDerivative($t, $coefficients, $ncoe) / $dseg * 2.0
                : 0.0;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $entry['segment'],
            't' => $t,
            'position' => $position,
            'speed' => $speed,
            'vector' => [
                $position[0],
                $position[1],
                $position[2],
                $speed[0],
                $speed[1],
                $speed[2],
            ],
            'referenceEllipseApplied' => $segment['referenceEllipseApplied'],
            'qav' => $segment['qav'],
            'pav' => $segment['pav'],
            'neval' => $segment['neval'],
            'nEvaluate' => $segment['nEvaluate'],
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * Evaluates raw Chebyshev segment coefficients from the ephemeris file.
     *
     * This is useful for tests and debugging the file decoder. It intentionally
     * does not apply the reference ellipse or rot_back() rotation.
     *
     * @return array<string, mixed>
     */
    public static function rawSegmentVector(string $path, int $ipl, float $tjdEt, bool $withSpeed = true): array
    {
        $entry = self::segmentIndexEntry($path, $ipl, $tjdEt);

        if ($entry['rc'] !== Catalog::SE_OK) {
            return self::rawSegmentVectorError($path, $ipl, $entry['error']);
        }

        $segment = self::segmentCoefficients($path, $ipl, $tjdEt);

        if ($segment['rc'] !== Catalog::SE_OK) {
            return self::rawSegmentVectorError($path, $ipl, $segment['error']);
        }

        $descriptor = $entry['descriptor'];
        $ncoe = (int)$descriptor['ncoe'];
        $dseg = (float)$descriptor['dseg'];
        $t = (($tjdEt - (float)$entry['tseg0']) / $dseg) * 2.0 - 1.0;

        $position = [];
        $speed = [];

        for ($coord = 0; $coord < 3; $coord++) {
            $coefficients = $segment['coefficients'][$coord];

            $position[] = self::evaluateChebyshev($t, $coefficients, $ncoe);
            $speed[] = $withSpeed
                ? self::evaluateChebyshevDerivative($t, $coefficients, $ncoe) / $dseg * 2.0
                : 0.0;
        }

        return [
            'rc' => Catalog::SE_OK,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => $entry['segment'],
            't' => $t,
            'position' => $position,
            'speed' => $speed,
            'vector' => [
                $position[0],
                $position[1],
                $position[2],
                $speed[0],
                $speed[1],
                $speed[2],
            ],
            'descriptor' => $descriptor,
            'error' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeCoordinateCoefficients(
        string $path,
        int    $offset,
        int    $ncoe,
        float  $rmax,
        string $endian
    ): array
    {
        $header = self::readAt($path, $offset, 2);

        if ($header === null) {
            return self::coefficientDecodeError('cannot read ephemeris coefficient header');
        }

        $cursor = $offset + 2;
        $c0 = self::readUInt8($header[0]);
        $c1 = self::readUInt8($header[1]);

        if (($c0 & 128) !== 0) {
            $extra = self::readAt($path, $cursor, 2);

            if ($extra === null) {
                return self::coefficientDecodeError('cannot read extended ephemeris coefficient header');
            }

            $cursor += 2;
            $c2 = self::readUInt8($extra[0]);
            $c3 = self::readUInt8($extra[1]);

            $sizes = [
                intdiv($c1, 16),
                $c1 % 16,
                intdiv($c2, 16),
                $c2 % 16,
                intdiv($c3, 16),
                $c3 % 16,
            ];
        } else {
            $sizes = [
                intdiv($c0, 16),
                $c0 % 16,
                intdiv($c1, 16),
                $c1 % 16,
            ];
        }

        if (array_sum($sizes) > $ncoe) {
            return self::coefficientDecodeError('ephemeris coefficient count exceeds descriptor order');
        }

        $coefficients = [];

        foreach ($sizes as $sizeIndex => $count) {
            if ($count === 0) {
                continue;
            }

            if ($sizeIndex < 4) {
                $byteCount = 4 - $sizeIndex;

                for ($i = 0; $i < $count; $i++) {
                    $bytes = self::readAt($path, $cursor, $byteCount);

                    if ($bytes === null) {
                        return self::coefficientDecodeError('cannot read packed ephemeris coefficient');
                    }

                    $cursor += $byteCount;
                    $value = self::readPackedUnsigned($bytes, $endian);
                    $coefficients[] = self::decodePackedCoefficient($value, $rmax);
                }

                continue;
            }

            if ($sizeIndex === 4) {
                $packedCount = intdiv($count + 1, 2);
                $done = 0;

                for ($i = 0; $i < $packedCount && $done < $count; $i++) {
                    $bytes = self::readAt($path, $cursor, 1);

                    if ($bytes === null) {
                        return self::coefficientDecodeError('cannot read half-byte ephemeris coefficient');
                    }

                    $cursor++;
                    $value = self::readUInt8($bytes);
                    $mask = 16;

                    for ($j = 0; $j < 2 && $done < $count; $j++, $done++) {
                        $coefficients[] = self::decodeSubByteCoefficient($value, $mask, $rmax);
                        $value %= $mask;
                        $mask = intdiv($mask, 16);
                    }
                }

                continue;
            }

            if ($sizeIndex === 5) {
                $packedCount = intdiv($count + 3, 4);
                $done = 0;

                for ($i = 0; $i < $packedCount && $done < $count; $i++) {
                    $bytes = self::readAt($path, $cursor, 1);

                    if ($bytes === null) {
                        return self::coefficientDecodeError('cannot read quarter-byte ephemeris coefficient');
                    }

                    $cursor++;
                    $value = self::readUInt8($bytes);
                    $mask = 64;

                    for ($j = 0; $j < 4 && $done < $count; $j++, $done++) {
                        $coefficients[] = self::decodeSubByteCoefficient($value, $mask, $rmax);
                        $value %= $mask;
                        $mask = intdiv($mask, 4);
                    }
                }
            }
        }

        while (count($coefficients) < $ncoe) {
            $coefficients[] = 0.0;
        }

        return [
            'rc' => Catalog::SE_OK,
            'sizes' => $sizes,
            'coefficients' => $coefficients,
            'nextOffset' => $cursor,
            'error' => '',
        ];
    }

    /**
     * @return list<string>
     */
    public static function files(): array
    {
        if (self::$path === '' || !is_dir(self::$path)) {
            return [];
        }

        $files = glob(self::$path . DIRECTORY_SEPARATOR . '*.se1');

        if ($files === false) {
            return [];
        }

        sort($files);

        return array_values(array_map('basename', $files));
    }

    private static function fileBodyNumber(int $body): int
    {
        if ($body === Catalog::SE_CERES) {
            return Catalog::SE_MEAN_APOG;
        }

        if ($body === Catalog::SE_PALLAS) {
            return Catalog::SE_OSCU_APOG;
        }

        if ($body === Catalog::SE_JUNO) {
            return Catalog::SE_EARTH;
        }

        if ($body === Catalog::SE_VESTA) {
            return Catalog::SE_CHIRON;
        }

        if ($body >= Catalog::SE_AST_OFFSET) {
            return $body - Catalog::SE_AST_OFFSET + Catalog::SE_MEAN_APOG - 1;
        }

        return $body;
    }

    private static function typeForBody(int $body): string
    {
        if ($body === Catalog::SE_MOON) {
            return self::TYPE_MOON;
        }

        if ($body >= Catalog::SE_AST_OFFSET) {
            return self::TYPE_ASTEROID;
        }

        return self::TYPE_PLANET;
    }

    private static function prefix(string $type): ?string
    {
        return match ($type) {
            self::TYPE_PLANET => 'sepl',
            self::TYPE_MOON => 'semo',
            self::TYPE_MAIN_ASTEROID, self::TYPE_ASTEROID => 'seas',
            default => null,
        };
    }

    private static function segmentSuffix(float $tjdEt): string
    {
        $date = SwissDate::revjul($tjdEt, SwissDate::GREGORIAN_CALENDAR);
        $year = (int)$date['year'];

        $segmentStart = (int)floor($year / 600) * 600;
        $century = intdiv(abs($segmentStart), 100);

        if ($segmentStart < 0) {
            return 'm' . str_pad((string)$century, 2, '0' . STR_PAD_LEFT);
        }

        return '_' . str_pad((string)$century, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{offset:int, endian:string}|null
     */
    private static function findEndianMarker(string $bytes): ?array
    {
        $length = strlen($bytes);

        for ($offset = 0; $offset <= $length - 4; $offset++) {
            $marker = substr($bytes, $offset, 4);

            if ($marker === self::MARKER_LITTLE_ENDIAN) {
                return [
                    'offset' => $offset,
                    'endian' => 'little',
                ];
            }
        }

        return null;
    }

    private static function readAt(string $path, int $offset, int $length): ?string
    {
        $bytes = file_get_contents($path, false, null, $offset, $length);

        if ($bytes === false || strlen($bytes) !== $length) {
            return null;
        }

        return $bytes;
    }

    private static function readUInt8(string $bytes): int
    {
        return ord($bytes);
    }

    private static function readUInt16(string $bytes, string $endian): int
    {
        $value = unpack($endian === 'little' ? 'vvalue' : 'nvalue', $bytes);

        return (int)$value['value'];
    }

    private static function readUInt24(string $bytes, string $endian): int
    {
        $value = unpack('C3', $bytes);

        if ($endian === 'little') {
            return (int)($value[1] + ($value[2] << 8) + ($value[3] << 16));
        }

        return (int)(($value[1] << 16) + ($value[2] << 8) + $value[3]);
    }

    private static function readUInt32(string $bytes, string $endian): int
    {
        $value = unpack($endian === 'little' ? 'Vvalue' : 'Nvalue', $bytes);

        return (int)$value['value'];
    }

    private static function readDouble(string $bytes, string $endian): float
    {
        $value = unpack($endian === 'little' ? 'evalue' : 'Evalue', $bytes);

        return (float)$value['value'];
    }

    /**
     * @return list<float>|null
     */
    private static function readDoubleList(string $path, int $offset, int $count, string $endian): ?array
    {
        $bytes = self::readAt($path, $offset, $count * 8);

        if ($bytes === null) {
            return null;
        }

        $values = [];

        for ($i = 0; $i < $count; $i++) {
            $values[] = self::readDouble(substr($bytes, $i * 8, 8), $endian);
        }

        return $values;
    }

    private static function readPackedUnsigned(string $bytes, string $endian): int
    {
        $value = 0;
        $length = strlen($bytes);

        if ($endian === 'little') {
            for ($i = 0; $i < $length; $i++) {
                $value += ord($bytes[$i]) << (8 * $i);
            }

            return $value;
        }

        for ($i = 0; $i < $length; $i++) {
            $value = ($value << 8) + ord($bytes[$i]);
        }

        return $value;
    }

    private static function decodePackedCoefficient(int $value, float $rmax): float
    {
        if (($value & 1) !== 0) {
            return -((($value + 1) / 2) / 1e9 * $rmax / 2);
        }

        return ($value / 2) / 1e9 * $rmax / 2;
    }

    private static function decodeSubByteCoefficient(int $value, int $mask, float $rmax): float
    {
        if (($value & $mask) !== 0) {
            return -((($value + $mask) / $mask / 2) * $rmax / 2 / 1e9);
        }

        return ($value / $mask / 2) * $rmax / 2 / 1e9;
    }

    /**
     * @param list<float> $coefficients
     */
    private static function evaluateChebyshev(float $x, array $coefficients, int $ncf): float
    {
        $x2 = $x * 2.0;
        $br = 0.0;
        $brp2 = 0.0;
        $brpp = 0.0;

        for ($j = $ncf - 1; $j >= 0; $j--) {
            $brp2 = $brpp;
            $brpp = $br;
            $br = $x2 * $brpp - $brp2 + $coefficients[$j];
        }

        return ($br - $brp2) * 0.5;
    }

    /**
     * @param list<float> $coefficients
     */
    private static function evaluateChebyshevDerivative(float $x, array $coefficients, int $ncf): float
    {
        $x2 = $x * 2.0;
        $bf = 0.0;
        $bj = 0.0;
        $xjp2 = 0.0;
        $xjpl = 0.0;
        $bjp2 = 0.0;
        $bjpl = 0.0;

        for ($j = $ncf - 1; $j >= 1; $j--) {
            $dj = (float)($j + $j);
            $xj = $coefficients[$j] * $dj + $xjp2;
            $bj = $x2 * $bjpl - $bjp2 + $xj;
            $bf = $bjp2;
            $bjp2 = $bjpl;
            $bjpl = $bj;
            $xjp2 = $xjpl;
            $xjpl = $xj;
        }

        return ($bj - $bf) * 0.5;
    }

    private static function normalizeRadians(float $value): float
    {
        $twoPi = 2.0 * M_PI;
        $value = fmod($value, $twoPi);

        if ($value < 0.0) {
            return $value + $twoPi;
        }

        return $value;
    }

    /**
     * @return array{rc:int, type:string, file:string, path:string, error:string}
     */
    private static function error(string $type, string $file, string $path, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'type' => $type,
            'file' => $file,
            'path' => $path,
            'error' => $error,
        ];
    }

    /**
     * @return array{
     *     rc:int,
     *     path:string,
     *     file:string,
     *     endian:string,
     *     markerOffset:int,
     *     dataOffset:int,
     *     planetDataOffset:int,
     *     fileLength:int,
     *     actualFileLength:int,
     *     denum:int,
     *     tfstart:float,
     *     tfend:float,
     *     nplan:int,
     *     ipl:list<int>,
     *     error:string
     * }
     */
    private static function metadataError(string $path, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'endian' => '',
            'markerOffset' => -1,
            'dataOffset' => -1,
            'crcOffset' => -1,
            'generalConstantsOffset' => -1,
            'bodyDescriptorsOffset' => -1,
            'planetDataOffset' => -1,
            'fileLength' => 0,
            'actualFileLength' => 0,
            'denum' => 0,
            'tfstart' => 0.0,
            'tfend' => 0.0,
            'nplan' => 0,
            'ipl' => [],
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function descriptorError(string $path, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'metadata' => null,
            'descriptors' => [],
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function segmentIndexError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            'indexOffset' => -1,
            'segmentOffset' => -1,
            'tseg0' => 0.0,
            'tseg1' => 0.0,
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function coefficientDecodeError(string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'sizes' => [],
            'coefficients' => [],
            'nextOffset' => -1,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function segmentCoefficientsError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            'segmentOffset' => -1,
            'nextOffset' => -1,
            'coordinateSizes' => [],
            'coefficients' => [],
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rawSegmentVectorError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            't' => 0.0,
            'position' => [],
            'speed' => [],
            'vector' => [],
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function referenceEllipseCoefficientsError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            'segmentOffset' => -1,
            'nextOffset' => -1,
            'coordinateSizes' => [],
            'coefficients' => [],
            'referenceEllipseApplied' => false,
            'omtild' => 0.0,
            'cosOmtild' => 0.0,
            'sinOmtild' => 0.0,
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function referenceEllipseVectorError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            't' => 0.0,
            'position' => [],
            'speed' => [],
            'vector' => [],
            'referenceEllipseApplied' => false,
            'omtild' => 0.0,
            'cosOmtild' => 1.0,
            'sinOmtild' => 0.0,
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rotatedCoefficientsError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            'segmentOffset' => -1,
            'nextOffset' => -1,
            'coordinateSizes' => [],
            'coefficients' => [],
            'referenceEllipseApplied' => false,
            'qav' => 0.0,
            'pav' => 0.0,
            'uix' => [],
            'uiy' => [],
            'uiz' => [],
            'neval' => 0,
            'nEvaluate' => 0,
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rotatedVectorError(string $path, int $ipl, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'path' => $path,
            'file' => basename($path),
            'ipl' => $ipl,
            'segment' => -1,
            't' => 0.0,
            'position' => [],
            'speed' => [],
            'vector' => [],
            'referenceEllipseApplied' => false,
            'qav' => 0.0,
            'pav' => 0.0,
            'neval' => 0,
            'nEvaluate' => 0,
            'descriptor' => null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function positionError(int $body, string $type, string $file, string $path, string $error): array
    {
        return [
            'rc' => Catalog::SE_ERR,
            'body' => $body,
            'ipl' => self::fileBodyNumber($body),
            'type' => $type,
            'file' => $file,
            'path' => $path,
            'segment' => -1,
            'position' => [],
            'speed' => [],
            'vector' => [],
            'metadata' => null,
            'descriptor' => null,
            'error' => $error,
        ];
    }
}