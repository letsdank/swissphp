<?php

declare(strict_types=1);

use SwissEph\AspectSet;
use SwissEph\Catalog;
use SwissEph\Houses;
use SwissEph\NatalChartFacade;
use SwissEph\SwissDate;

require __DIR__ . '/../vendor/autoload.php';

$svg = NatalChartFacade::svgFromLocalDateTime(
    year: 2000,
    month: 1,
    day: 1,
    hour: 15,
    minute: 0,
    second: 0.0,
    timezone: 3.0,
    geoLat: 55.7558,
    geoLon: 37.6173,
    houseSystem: Houses::HSYS_PLACIDUS,
    bodies: [
        Catalog::SE_SUN,
        Catalog::SE_MOON,
        Catalog::SE_MERCURY,
        Catalog::SE_VENUS,
        Catalog::SE_MARS,
        Catalog::SE_JUPITER,
        Catalog::SE_SATURN,
        Catalog::SE_URANUS,
        Catalog::SE_NEPTUNE,
        Catalog::SE_PLUTO,
    ],
    flags: Catalog::SEFLG_DEFAULTEPH,
    aspectSet: AspectSet::major(8.0),
    calendar: SwissDate::GREGORIAN_CALENDAR,
    size: 720
);

$file = __DIR__ . '/natal-chart.svg';
file_put_contents($file, $svg);

echo $file . PHP_EOL;