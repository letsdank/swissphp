<?php

declare(strict_types=1);

namespace SwissEph;

final class AspectMatrix
{
    /**
     * @param array<string, CalculationResult> $positions
     * @return array<int, array{first:string, second:string, aspect:AspectResult}>
     */
    public static function between(array $positions, AspectSet $aspectSet): array
    {
        $keys = array_keys($positions);
        $results = [];

        $count = count($keys);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $firstKey = $keys[$i];
                $secondKey = $keys[$j];

                $aspect = $positions[$firstKey]->aspectResultFromSetTo($positions[$secondKey], $aspectSet);

                if ($aspect === null) {
                    continue;
                }

                $results[] = [
                    'first' => $firstKey,
                    'second' => $secondKey,
                    'aspect' => $aspect,
                ];
            }
        }

        return $results;
    }

    /**
     * @param array<string, CalculationResult> $firstPositions
     * @param array<string, CalculationResult> $secondPositions
     * @return array<int, array{first:string, second:string, aspect:AspectResult}>
     */
    public static function cross(array $firstPositions, array $secondPositions, AspectSet $aspectSet): array
    {
        $results = [];

        foreach ($firstPositions as $firstKey => $firstPosition) {
            foreach ($secondPositions as $secondKey => $secondPosition) {
                $aspect = $firstPosition->aspectResultFromSetTo($secondPosition, $aspectSet);

                if ($aspect === null) {
                    continue;
                }

                $results[] = [
                    'first' => $firstKey,
                    'second' => $secondKey,
                    'aspect' => $aspect,
                ];
            }
        }

        return $results;
    }
}