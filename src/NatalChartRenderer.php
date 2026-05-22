<?php

declare(strict_types=1);

namespace SwissEph;

final class NatalChartRenderer
{
    private const SIGN_GLYPHS = [
        'Aries' => 'Ar',
        'Taurus' => 'Ta',
        'Gemini' => 'Ge',
        'Cancer' => 'Cn',
        'Leo' => 'Le',
        'Virgo' => 'Vi',
        'Libra' => 'Li',
        'Scorpio' => 'Sc',
        'Sagittarius' => 'Sg',
        'Capricorn' => 'Cp',
        'Aquarius' => 'Aq',
        'Pisces' => 'Pi',
    ];

    private const BODY_GLYPHS = [
        'Sun' => 'Sun',
        'Moon' => 'Moon',
        'Mercury' => 'Me',
        'Venus' => 'Ve',
        'Mars' => 'Ma',
        'Jupiter' => 'Ju',
        'Saturn' => 'Sa',
        'Uranus' => 'Ur',
        'Neptune' => 'Ne',
        'Pluto' => 'Pl',
    ];

    public static function renderSvg(NatalChart $chart, int $size = 720): string
    {
        $size = max(320, $size);
        $center = $size / 2.0;
        $outerRadius = $size * 0.46;
        $zodiacRadius = $size * 0.405;
        $houseRadius = $size * 0.335;
        $planetRadius = $size * 0.295;
        $aspectRadius = $size * 0.235;

        $parts = [
            self::svgOpen($size),
            self::circle($center, $center, $outerRadius, '#f8fafc', '#111827', 1.5),
            self::circle($center, $center, $zodiacRadius, 'none', '#475569', 1.0),
            self::circle($center, $center, $houseRadius, 'none', '#94a3b8', 1.0),
            self::renderZodiac($center, $outerRadius, $zodiacRadius),
            self::renderHouses($chart, $center, $outerRadius, $houseRadius),
            self::renderAngles($chart, $center, $outerRadius, $houseRadius),
            self::renderAspects($chart, $center, $aspectRadius),
            self::renderPoints($chart, $center, $planetRadius),
            '</svg>',
        ];

        return implode("\n", array_filter($parts, static fn(string $part): bool => $part !== ''));
    }

    private static function svgOpen(int $size): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" role="img" aria-label="Natal chart">',
            $size,
            $size,
            $size,
            $size
        );
    }

    private static function renderZodiac(float $center, float $outerRadius, float $zodiacRadius): string
    {
        $parts = [];

        for ($i = 0; $i < 12; $i++) {
            $longitude = $i * 30.0;
            [$x1, $y1] = self::point($center, $outerRadius, $longitude);
            [$x2, $y2] = self::point($center, $zodiacRadius, $longitude);

            $parts[] = self::line($x1, $y1, $x2, $y2, '#64748b', 1.0);

            [$tx, $ty] = self::point($center, ($outerRadius + $zodiacRadius) / 2.0, $longitude + 15.0);
            $signName = array_keys(self::SIGN_GLYPHS)[$i];
            $parts[] = self::text($tx, $ty, self::SIGN_GLYPHS[$signName], 13, '#0f172a', 'middle');
        }

        return implode("\n", $parts);
    }

    private static function renderHouses(NatalChart $chart, float $center, float $outerRadius, float $houseRadius): string
    {
        $parts = [];

        foreach ($chart->houses as $house) {
            [$x1, $y1] = self::point($center, $outerRadius, $house->cusp);
            [$x2, $y2] = self::point($center, $houseRadius, $house->cusp);
            [$tx, $ty] = self::point($center, $houseRadius - 18.0, $house->cusp + 15.0);

            $stroke = $house->number === 1 || $house->number === 4 || $house->number === 7 || $house->number === 10
                ? '#0f172a'
                : '#cbd5e1';

            $width = $house->number === 1 || $house->number === 10 ? 1.6 : 0.8;

            $parts[] = self::line($x1, $y1, $x2, $y2, $stroke, $width);
            $parts[] = self::text($tx, $ty, (string)$house->number, 11, '#64748b', 'middle');
        }

        return implode("\n", $parts);
    }

    private static function renderAngles(NatalChart $chart, float $center, float $outerRadius, float $houseRadius): string
    {
        if (!isset($chart->houses[1], $chart->houses[4], $chart->houses[7], $chart->houses[10])) {
            return '';
        }

        $angles = [
            'ASC' => $chart->houses[1]->cusp,
            'IC' => $chart->houses[4]->cusp,
            'DSC' => $chart->houses[7]->cusp,
            'MC' => $chart->houses[10]->cusp,
        ];

        $parts = [];

        foreach ($angles as $label => $longitude) {
            [$x1, $y1] = self::point($center, $outerRadius, $longitude);
            [$x2, $y2] = self::point($center, $houseRadius - 12.0, $longitude);
            [$tx, $ty] = self::point($center, $outerRadius + 16.0, $longitude);

            $parts[] = self::line($x1, $y1, $x2, $y2, '#111827', 1.8);
            $parts[] = self::text($tx, $ty + 4.0, $label, 12, '#111827', 'middle');
        }

        return implode("\n", $parts);
    }

    private static function renderPoints(NatalChart $chart, float $center, float $planetRadius): string
    {
        $parts = [];
        $points = array_values($chart->points);
        usort(
            $points,
            static fn(NatalChartPoint $a, NatalChartPoint $b): int => $a->normalizedLongitude() <=> $b->normalizedLongitude()
        );

        $layout = self::pointLayout($points);

        foreach ($points as $index => $point) {
            $radius = $planetRadius - $layout[$index] * 19.0;
            [$x, $y] = self::point($center, $radius, $point->longitude);
            [$tickX1, $tickY1] = self::point($center, $planetRadius + 13.0, $point->longitude);
            [$tickX2, $tickY2] = self::point($center, $planetRadius - 58.0, $point->longitude);

            $label = self::BODY_GLYPHS[$point->name] ?? $point->name;
            $retrograde = $point->isRetrograde() ? ' R' : '';

            $parts[] = self::line($tickX1, $tickY1, $tickX2, $tickY2, '#cbd5e1', 0.7, 0.85);
            $parts[] = self::circle($x, $y, 13.0, '#ffffff', '#334155', 1.0);
            $parts[] = self::text($x, $y + 4.0, $label . $retrograde, 10, '#0f172a', 'middle');
        }

        return implode("\n", $parts);
    }

    /**
     * @param array<int, NatalChartPoint> $points
     * @return array<int, int>
     */
    private static function pointLayout(array $points): array
    {
        $layout = [];

        foreach ($points as $index => $point) {
            $level = 0;

            for ($previous = $index - 1; $previous >= 0; $previous--) {
                $distance = abs(Angle::difdeg2n(
                    $point->normalizedLongitude(),
                    $points[$previous]->normalizedLongitude()
                ));

                if ($distance > 7.0) {
                    break;
                }

                $level = max($level, ($layout[$previous] ?? 0) + 1);
            }

            $layout[$index] = min($level, 4);
        }

        return $layout;
    }

    private static function renderAspects(NatalChart $chart, float $center, float $aspectRadius): string
    {
        if ($chart->aspects === []) {
            return '';
        }

        $parts = [];

        foreach ($chart->aspects as $aspect) {
            if (!isset($chart->points[$aspect->first], $chart->points[$aspect->second])) {
                continue;
            }

            $first = $chart->points[$aspect->first];
            $second = $chart->points[$aspect->second];

            [$x1, $y1] = self::point($center, $aspectRadius, $first->longitude);
            [$x2, $y2] = self::point($center, $aspectRadius, $second->longitude);

            $parts[] = self::line($x1, $y1, $x2, $y2, self::aspectColor($aspect->angle), 0.7, 0.55);
        }

        return implode("\n", $parts);
    }

    private static function aspectColor(float $angle): string
    {
        return match ((int)round($angle)) {
            0 => '#64748b',
            60, 120 => '#2563eb',
            90, 180 => '#dc2626',
            default => '#7c3aed',
        };
    }

    /**
     * Converts ecliptic longitude to SVG coordinates.
     * Zero Aries is drawn on the left, and longitudes increase clockwise.
     *
     * @return array{0:float, 1:float}
     */
    private static function point(float $center, float $radius, float $longitude): array
    {
        $angle = deg2rad(180.0 - Angle::degnorm($longitude));

        return [
            $center + cos($angle) * $radius,
            $center - sin($angle) * $radius,
        ];
    }

    private static function circle(float $cx, float $cy, float $r, string $fill, string $stroke, float $strokeWidth): string
    {
        return sprintf(
            '<circle cx="%.3F" cy="%.3F" r="%.3F" fill="%s" stroke="%s" stroke-width="%.3F"/>',
            $cx,
            $cy,
            $r,
            self::escape($fill),
            self::escape($stroke),
            $strokeWidth,
        );
    }

    private static function line(
        float  $x1,
        float  $y1,
        float  $x2,
        float  $y2,
        string $stroke,
        float  $strokeWidth,
        float  $opacity = 1.0
    ): string
    {
        return sprintf(
            '<line x1="%.3F" y1="%.3F" x2="%.3F" y2="%.3F" stroke="%s" stroke-width="%.3F" opacity="%.3F"/>',
            $x1,
            $y1,
            $x2,
            $y2,
            self::escape($stroke),
            $strokeWidth,
            $opacity
        );
    }

    private static function text(
        float  $x,
        float  $y,
        string $text,
        int    $size,
        string $fill,
        string $anchor
    ): string
    {
        return sprintf(
            '<text x="%.3F" y="%.3F" font-family="Arial, sans-serif" font-size="%d" fill="%s" text-anchor="%s">%s</text>',
            $x,
            $y,
            $size,
            self::escape($fill),
            self::escape($anchor),
            self::escape($text)
        );
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}