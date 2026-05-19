<?php

declare(strict_types=1);

namespace SwissEph;

final class MoshierSeries
{
    /**
     * Evaluates a Moshier-style heliocentric polar series.
     *
     * The raw table format mirrors swemptab.h:
     * - argTable contains np, harmonic/argument pairs, nt, and final -1.
     * - lonTable/latTable/radTable contain polynomial coefficients in C order.
     *
     * Result:
     * [longitude radians, latitude radians, radius AU].
     *
     * @param array{
     *     distance:float,
     *     maxHarmonic:list<int>,
     *     argTable:list<int>,
     *     lonTable:list<float>,
     *     latTable:list<float>,
     *     radTable:list<float>
     * } $table
     *
     * @return array{0:float, 1:float, 2:float}
     */
    public static function evaluatePolar(float $julianDay, array $table): array
    {
        $t = Moshier::timeParameter($julianDay);
        $harmonics = self::buildHarmonics($julianDay, $table['maxHarmonic']);

        $argIndex = 0;
        $lonIndex = 0;
        $latIndex = 0;
        $radIndex = 0;

        $longitude = 0.0;
        $latitude = 0.0;
        $radius = 0.0;

        for (; ;) {
            $np = self::readInt($table['argTable'], $argIndex);

            if ($np < 0) {
                break;
            }

            if ($np === 0) {
                $nt = self::readInt($table['argTable'], $argIndex);

                $longitude += Moshier::mods3600(
                    self::evaluatePolynomial($table['lonTable'], $lonIndex, $t, $nt)
                );
                $latitude += self::evaluatePolynomial($table['latTable'], $latIndex, $t, $nt);
                $radius += self::evaluatePolynomial($table['radTable'], $radIndex, $t, $nt);

                continue;
            }

            $sinArgument = 0.0;
            $cosArgument = 0.0;
            $hasArgument = false;

            for ($i = 0; $i < $np; $i++) {
                $harmonic = self::readInt($table['argTable'], $argIndex);
                $argumentIndex = self::readInt($table['argTable'], $argIndex) - 1;

                if ($harmonic === 0) {
                    continue;
                }

                $absHarmonic = abs($harmonic);
                $entry = $harmonics[$argumentIndex][$absHarmonic] ?? null;

                if ($entry === null) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Missing Moshier harmonic %d for argument %d.',
                            $absHarmonic,
                            $argumentIndex
                        )
                    );
                }

                $sinPart = $entry['sin'];
                $cosPart = $entry['cos'];

                if ($harmonic < 0) {
                    $sinPart = -$sinPart;
                }

                if (!$hasArgument) {
                    $sinArgument = $sinPart;
                    $cosArgument = $cosPart;
                    $hasArgument = true;
                    continue;
                }

                $combinedSin = $sinPart * $cosArgument + $cosPart * $sinArgument;
                $combinedCos = $cosPart * $cosArgument - $sinPart * $sinArgument;

                $sinArgument = $combinedSin;
                $cosArgument = $combinedCos;
            }

            $nt = self::readInt($table['argTable'], $argIndex);

            [$cosCoefficient, $sinCoefficient] = self::evaluateSinCosPolynomial(
                $table['lonTable'],
                $lonIndex,
                $t,
                $nt
            );
            $longitude += $cosCoefficient * $cosArgument + $sinCoefficient * $sinArgument;

            [$cosCoefficient, $sinCoefficient] = self::evaluateSinCosPolynomial(
                $table['latTable'],
                $latIndex,
                $t,
                $nt
            );
            $latitude += $cosCoefficient * $cosArgument + $sinCoefficient * $sinArgument;

            [$cosCoefficient, $sinCoefficient] = self::evaluateSinCosPolynomial(
                $table['radTable'],
                $radIndex,
                $t,
                $nt
            );
            $radius += $cosCoefficient * $cosArgument + $sinCoefficient * $sinArgument;
        }

        $distance = $table['distance'];

        return [
            Moshier::ARCSEC_TO_RAD * $longitude,
            Moshier::ARCSEC_TO_RAD * $latitude,
            Moshier::ARCSEC_TO_RAD * $distance * $radius + $distance,
        ];
    }

    /**
     * @param list<int> $maxHarmonic
     * @return array<int, array<int, array{sin:float, cos:float}>>
     */
    private static function buildHarmonics(float $julianDay, array $maxHarmonic): array
    {
        $harmonics = [];

        for ($i = 0; $i < 9; $i++) {
            $max = $maxHarmonic[$i] ?? 0;

            if ($max > 0) {
                $harmonics[$i] = Moshier::harmonicSinCos(
                    Moshier::meanArgument($i, $julianDay),
                    $max
                );
            }
        }

        return $harmonics;
    }

    /**
     * @param list<float> $table
     */
    private static function evaluatePolynomial(array $table, int &$index, float $t, int $degree): float
    {
        $value = self::readFloat($table, $index);

        for ($i = 0; $i < $degree; $i++) {
            $value = $value * $t + self::readFloat($table, $index);
        }

        return $value;
    }

    /**
     * @param list<float> $table
     * @return array{0:float, 1:float}
     */
    private static function evaluateSinCosPolynomial(array $table, int &$index, float $t, int $degree): array
    {
        $cosCoefficient = self::readFloat($table, $index);
        $sinCoefficient = self::readFloat($table, $index);

        for ($i = 0; $i < $degree; $i++) {
            $cosCoefficient = $cosCoefficient * $t + self::readFloat($table, $index);
            $sinCoefficient = $sinCoefficient * $t + self::readFloat($table, $index);
        }

        return [$cosCoefficient, $sinCoefficient];
    }

    /**
     * @param list<int> $table
     */
    private static function readInt(array $table, int &$index): int
    {
        if (!array_key_exists($index, $table)) {
            throw new \InvalidArgumentException('Unexpected end of Moshier argument table.');
        }

        return $table[$index++];
    }

    /**
     * @param list<float> $table
     */
    private static function readFloat(array $table, int &$index): float
    {
        if (!array_key_exists($index, $table)) {
            throw new \InvalidArgumentException('Unexpected end of Moshier coefficient table.');
        }

        return $table[$index++];
    }
}