<?php

declare(strict_types=1);

namespace SwissEph;

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
}