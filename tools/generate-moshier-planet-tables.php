<?php

declare(strict_types=1);

const PLANETS = [
    'MERCURY' => ['struct' => 'mer404', 'method' => 'mercury'],
    'VENUS' => ['struct' => 'ven404', 'method' => 'venus'],
    'EARTH' => ['struct' => 'ear404', 'method' => 'earth'],
    'MARS' => ['struct' => 'mar404', 'method' => 'mars'],
    'JUPITER' => ['struct' => 'jup404', 'method' => 'jupiter'],
    'SATURN' => ['struct' => 'sat404', 'method' => 'saturn'],
    'URANUS' => ['struct' => 'ura404', 'method' => 'uranus'],
    'NEPTUNE' => ['struct' => 'nep404', 'method' => 'neptune'],
    'PLUTO' => ['struct' => 'plu404', 'method' => 'pluto'],
];

$input = $argv[1] ?? dirname(__DIR__) . '/../vendor/swisseph-2.10.03/swemptab.h';
$output = $argv[2] ?? dirname(__DIR__) . '/src/MoshierPlanetTables.php';

$source = file_get_contents($input);
if ($source === false) {
    fwrite(STDERR, sprintf("Cannot read %s\n", $input));
    exit(1);
}

$code = "<?php\n\n";
$code .= "declare(strict_types=1);\n\n";
$code .= "namespace SwissEph;\n\n";
$code .= "final class MoshierPlanetTables\n";
$code .= "{\n";

$index = 0;
foreach (array_keys(PLANETS) as $name) {
    $code .= sprintf("    public const %s = %d;\n", $name, $index++);
}

$code .= "\n";
$code .= "    /**\n";
$code .= "     * @return array{distance:float, maxHarmonic:list<int>, maxPower:int, argTable:list<int>, lonTable:list<float>, latTable:list<float>, radTable:list<float>}\n";
$code .= "     */\n";
$code .= "    public static function planet(int \$planet): array\n";
$code .= "    {\n";
$code .= "        return match(\$planet) {\n";

foreach (PLANETS as $name => $planet) {
    $code .= sprintf("            self::%s => self::%s(),\n", $name, $planet['method']);
}

$code .= "            default => throw new \\InvalidArgumentException(sprintf('Unknown Moshier planet %d.', \$planet)),\n";
$code .= "        };\n";
$code .= "    }\n";

foreach (PLANETS as $name => $planet) {
    $table = extractPlanetTable($source, $planet['struct']);

    $code .= "\n";
    $code .= "    /**\n";
    $code .= "     * @return array{distance:float, maxHarmonic:list<int>, maxPower:int, argTable:list<int>, lonTable:list<float>, latTable:list<float>, radTable:list<float>}\n";
    $code .= "     */\n";
    $code .= sprintf("    public static function %s(): array\n", $planet['method']);
    $code .= "    {\n";
    $code .= "        return [\n";
    $code .= sprintf("            'distance' => %s,\n", formatFloat($table['distance']));
    $code .= "            'maxHarmonic' => " . formatArray($table['maxHarmonic'], 12, false) . ",\n";
    $code .= sprintf("            'maxPower' => %d,\n", $table['maxPower']);
    $code .= "            'argTable' => " . formatArray($table['argTable'], 12, false) . ",\n";
    $code .= "            'lonTable' => " . formatArray($table['lonTable'], 12, true) . ",\n";
    $code .= "            'latTable' => " . formatArray($table['latTable'], 12, true) . ",\n";
    $code .= "            'radTable' => " . formatArray($table['radTable'], 12, true) . ",\n";
    $code .= "        ];\n";
    $code .= "    }\n";
}

$code .= "}\n";

if (file_put_contents($output, $code) === false) {
    fwrite(STDERR, sprintf("Cannot write %s\n", $output));
    exit(1);
}

printf("Generated %s\n", $output);

/**
 * @return array{distance:float, maxHarmonic:list<int>, maxPower:int, argTable:list<int>, lonTable:list<float>, latTable:list<float>, radTable:list<float>}
 */
function extractPlanetTable(string $source, string $structName): array
{
    $pattern = '~static\s+struct\s+plantbl\s+' . preg_quote($structName, '~') . '\s*=\s*\{\s*'
        . '\{(?P<maxHarmonic>.*?)\}\s*,\s*'
        . '(?P<maxPower>-?\d+)\s*,\s*'
        . '(?P<argTable>[a-z]+args)\s*,\s*'
        . '(?P<lonTable>[a-z]+tabl)\s*,\s*'
        . '(?P<latTable>[a-z]+tabb)\s*,\s*'
        . '(?P<radTable>[a-z]+tabr)\s*,\s*'
        . '(?P<distance>[-+]?(?:\d+\.\d*|\.\d+|\d+)(?:[eE][-+]?\d+)?)\s*,\s*'
        . '\};~s';

    if (!preg_match($pattern, $source, $matches)) {
        throw new RuntimeException(sprintf('Cannot find planet table %s.', $structName));
    }

    return [
        'distance' => (float)$matches['distance'],
        'maxHarmonic' => parseInts($matches['maxHarmonic']),
        'maxPower' => (int)$matches['maxPower'],
        'argTable' => extractArray($source, $matches['argTable'], true),
        'lonTable' => extractArray($source, $matches['lonTable'], false),
        'latTable' => extractArray($source, $matches['latTable'], false),
        'radTable' => extractArray($source, $matches['radTable'], false),
    ];
}

/**
 * @return list<int|float>
 */
function extractArray(string $source, string $name, bool $asInt): array
{
    $pattern = '~static\s+(?:signed\s+char|double)\s+' . preg_quote($name, '~') . '\[\]\s*=\s*\{(.*?)\n\};~s';

    if (!preg_match($pattern, $source, $matches)) {
        throw new RuntimeException(sprintf('Cannot find array %s.', $name));
    }

    $body = preg_replace('~/\*.*?\*/~s', '', $matches[1]);
    if ($body === null) {
        throw new RuntimeException(sprintf('Cannot strip comments from %s.', $name));
    }

    return $asInt ? parseInts($body) : parseFloats($body);
}

/**
 * @return list<int>
 */
function parseInts(string $source): array
{
    preg_match_all('~-?\d+~', $source, $matches);

    return array_map(static fn(string $value): int => (int)$value, $matches[0]);
}

/**
 * @return list<float>
 */
function parseFloats(string $source): array
{
    preg_match_all('~[-+]?(?:\d+\.\d*|\.\d+|\d+)(?:[eE][-+]?\d+)?~', $source, $matches);

    return array_map(static fn(string $value): float => (float)$value, $matches[0]);
}

/**
 * @param list<int|float> $values
 */
function formatArray(array $values, int $indent, bool $floats): string
{
    if ($values === []) {
        return '';
    }

    $prefix = str_repeat(' ', $indent);
    $linePrefix = $prefix . '    ';
    $chunks = array_chunk($values, 8);
    $lines = ["["];

    foreach ($chunks as $chunk) {
        $items = array_map(
            static fn(int|float $value): string => $floats ? formatFloat((float)$value) : (string)$value,
            $chunk
        );

        $lines[] = $linePrefix . implode(', ', $items) . ',';
    }

    $lines[] = $prefix . ']';

    return implode("\n", $lines);
}

function formatFloat(float $value): string
{
    $formatted = sprintf('%.17G', $value);

    if (!str_contains($formatted, '.') && !str_contains($formatted, 'E')) {
        $formatted .= '.0';
    }

    return $formatted;
}