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
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $catalog->add(self::parseLine($line));
        }

        return $catalog;
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
    public static function parseLine(string $line): array
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
}