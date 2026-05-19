<?php

declare(strict_types=1);

namespace SwissEph;

final class EphemerisFiles
{
    public const TYPE_PLANET = 'planet';
    public const TYPE_MOON = 'moon';
    public const TYPE_MAIN_ASTEROID = 'main_asteroid';
    public const TYPE_ASTEROID = 'asteroid';

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

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [
                'rc' => Catalog::SE_ERR,
                'path' => $path,
                'file' => basename($path),
                'version' => '',
                'fileLine' => '',
                'copyright' => '',
                'raw' => '',
                'error' => 'cannot open ephemeris file',
            ];
        }

        $raw = fread($handle, 512);
        fclose($handle);

        if ($raw === false) {
            $raw = '';
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
}