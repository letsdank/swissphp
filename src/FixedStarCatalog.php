<?php

declare(strict_types=1);

namespace SwissEph;

final class FixedStarCatalog
{
    /**
     * @var array<string, array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}>
     */
    private array $stars = [];

    /**
     * @param array<string, array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}> $stars
     */
    public function __construct(array $stars = [])
    {
        foreach ($stars as $star) {
            $this->add($star);
        }
    }

    /**
     * @param array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float} $star
     */
    public function add(array $star): void
    {
        $normalized = self::normalizeStar($star);
        $this->stars[self::normalizeName($normalized['name'])] = $normalized;
    }

    /**
     * @return array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}|null
     */
    public function find(string $name): ?array
    {
        $key = self::normalizeName($name);

        if (isset($this->stars[$key])) {
            return $this->stars[$key];
        }

        foreach ($this->stars as $star) {
            foreach ($star['aliases'] as $alias) {
                if (self::normalizeName($alias) === $key) {
                    return $star;
                }
            }
        }

        return null;
    }

    public function exists(string $name): bool
    {
        return $this->find($name) !== null;
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_map(
            static fn(array $star): string => $star['name'],
            array_values($this->stars)
        );
    }

    /**
     * @return array<int, array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}>
     */
    public function all(): array
    {
        return array_values($this->stars);
    }

    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \InvalidArgumentException(sprintf('fixed star catalog file "%s" cannot be read', $path));
        }

        return self::fromString($contents);
    }

    public static function fromString(string $contents): self
    {
        $catalog = new self();
        $lines = preg_split('/\r\n|\n|\r/', $contents) ?: [];

        foreach ($lines as $line) {
            $line = trim(self::stripInlineComment($line));

            if ($line === '') {
                continue;
            }

            $catalog->add(self::parseLine($line));
        }

        return $catalog;
    }

    public static function parseLine(string $line): array
    {
        return str_contains($line, '|')
            ? self::parsePipeLine($line)
            : self::parseSwissLine($line);
    }

    /**
     * Simple CSV-like format:
     * name|aliases|ra|dec|pmRa|pmDec|parallax|mag
     *
     * aliases are comma-separated. RA/Dec are degrees, proper motions are
     * milliarcseconds/year, parallax is milliarcseconds.
     *
     * @return array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}
     */
    private static function parsePipeLine(string $line): array
    {
        $parts = array_map('trim', explode('|', $line));

        if (count($parts) < 4) {
            throw new \InvalidArgumentException('fixed star catalog line must contain at least name, aliases, ra, and dec');
        }

        $aliases = $parts[1] === ''
            ? []
            : array_values(array_filter(array_map('trim', explode(',', $parts[1])), static fn(string $value): bool => $value !== ''));

        return self::normalizeStar([
            'name' => $parts[0],
            'aliases' => $aliases,
            'ra' => (float)$parts[2],
            'dec' => (float)$parts[3],
            'pmRa' => (float)($parts[4] ?? 0.0),
            'pmDec' => (float)($parts[5] ?? 0.0),
            'parallax' => (float)($parts[6] ?? 0.0),
            'mag' => (float)($parts[7] ?? 0.0),
        ]);
    }

    /**
     * Swiss Ephemeris fixed star catalog rows are comma-separated.
     *
     * Supported compact shape:
     * name,nomname,epoch,ra,dec,pmRa,pmDec,radVel,parallax,mag
     *
     * RA/Dec are expected in decimal degrees here. Sexagesimal support can be
     * added separately once we lock a real fixture from sefstars.txt.
     *
     * @return array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}
     */
    private static function parseSwissLine(string $line): array
    {
        $parts = str_getcsv($line);

        if (count($parts) < 10) {
            throw new \InvalidArgumentException('fixed star catalog CSV line must contain at least 10 fields');
        }

        $parts = array_map(static fn(string $value) => trim($value), $parts);

        $name = $parts[0];
        $nominalName = $parts[1];

        $aliases = self::aliasesFromNames($name, $nominalName);

        return self::normalizeStar([
            'name' => $name,
            'aliases' => $aliases,
            'ra' => self::parseRightAscension($parts[3]),
            'dec' => self::parseDeclination($parts[4]),
            'pmRa' => (float)$parts[5],
            'pmDec' => (float)$parts[6],
            'parallax' => (float)$parts[8],
            'mag' => (float)$parts[9],
        ]);
    }

    /**
     * @param array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa?:float, pmDec?:float, parallax?:float, mag?:float} $star
     * @return array{name: string, aliases:array<int, string>, ra:float, dec:float, pmRa:float, pmDec:float, parallax:float, mag:float}
     */
    private static function normalizeStar(array $star): array
    {
        return [
            'name' => $star['name'],
            'aliases' => array_values($star['aliases'] ?? []),
            'ra' => $star['ra'],
            'dec' => $star['dec'],
            'pmRa' => $star['pmRa'] ?? 0.0,
            'pmDec' => $star['pmDec'] ?? 0.0,
            'parallax' => $star['parallax'] ?? 0.0,
            'mag' => $star['mag'] ?? 0.0,
        ];
    }

    public static function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(['_', '-'], ' ', $name);

        return preg_replace('/\s+/', ' ', $name) ?? $name;
    }

    private static function parseRightAscension(string $value): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        if (!str_contains($value, ' ') && !str_contains($value, ':')) {
            return (float)$value;
        }

        $parts = preg_split('/[:\s]+/', $value) ?: [];
        $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));

        $hours = (float)($parts[0] ?? 0.0);
        $minutes = (float)($parts[1] ?? 0.0);
        $seconds = (float)($parts[2] ?? 0.0);

        return ($hours + $minutes / 60.0 + $seconds / 3600.0) * 15.0;
    }

    private static function parseDeclination(string $value): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        if (!str_contains($value, ' ') && !str_contains($value, ':')) {
            return (float)$value;
        }

        $sign = str_starts_with($value, '-') ? -1.0 : 1.0;
        $value = ltrim($value, '+-');

        $parts = preg_split('/[:\s]+/', $value) ?: [];
        $parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));

        $degrees = abs((float)($parts[0] ?? 0.0));
        $minutes = (float)($parts[1] ?? 0.0);
        $seconds = (float)($parts[2] ?? 0.0);

        return $sign * ($degrees + $minutes / 60.0 + $seconds / 3600.0);
    }

    private static function stripInlineComment(string $line): string
    {
        $inQuote = false;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $inQuote = !$inQuote;
                continue;
            }

            if (!$inQuote && $char === '#') {
                return rtrim(substr($line, 0, $i));
            }
        }

        return $line;
    }

    /**
     * @return list<string>
     */
    private static function aliasesFromNames(string $name, string $nominalName): array
    {
        $aliases = [];

        foreach ([$nominalName, $name] as $value) {
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            foreach (preg_split('/[,;]+/', $value) ?: [] as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                if (self::normalizeName($part) === self::normalizeName($name)) {
                    continue;
                }

                $aliases[self::normalizeName($part)] = $part;
            }
        }

        return array_values($aliases);
    }
}