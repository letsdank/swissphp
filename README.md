# SwissPHP

[![CI](https://github.com/letsdank/swissphp/actions/workflows/ci.yml/badge.svg)](https://github.com/letsdank/swissphp/actions/workflows/ci.yml)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4.svg)](https://www.php.net/)

SwissPHP is a PHP port/reimplementation of core Swiss Ephemeris functionality.

The project is in active development. It already includes a tested calculation core for dates, delta T, sidereal time, coordinates, houses, Moshier planetary and lunar positions, rise/set calculations, phenomena, nodes/apsides, orbital elements, fixed stars and early support for Swiss Ephemeris files.

## Development workflow

This project is an incremental native PHP reimplementation of Swiss Ephemeris.
The translation and testing workflow is documented in
[docs/translation-workflow.md](docs/translation-workflow.md).

## Requirements

- PHP 8.4+
- Composer

## Installation

The package metadata is prepared for Composer usage, but tagged releases and
Packagist publication are still pending. Until the first release is tagged,
install from source.

```bash
git clone https://github.com/letsdank/swissphp.git
cd swissphp
composer install
```

For local development:

```bash
composer install
```

## Running Tests

```bash
vendor/bin/phpunit --configuration phpunit.xml.dist
```

## Example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use SwissEph\Calculator;
use SwissEph\Catalog;

$result = Calculator::calcUt(
    2451545.0,
    Catalog::SE_SUN,
    Catalog::SEFLG_MOSEPH
);

print_r($result);
```

## Natal chart

SwissPHP also includes a small application-level natal chart layer built on top
of the core ephemeris, house, and aspect calculations.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use SwissEph\AspectSet;
use SwissEph\Catalog;
use SwissEph\Houses;
use SwissEph\NatalChartFacade;
use SwissEph\SwissDate;

$chart = NatalChartFacade::fromLocalDateTime(
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
);

echo $chart->point('Sun')->signName();  // Capricorn
echo $chart->point('Sun')->house;       // House number
echo $chart->house(1)->cusp;            // Ascendant cusp

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
    aspectSet: AspectSet::major(8.0),
    size: 720,
);

file_put_contents(__DIR__ . '/natal-chart.svg', $svg);
```

## Status

This library is not a drop-in replacement for the original Swiss Ephemeris yet.

Implemented areas include:

- Julian date conversion
- Delta T
- Sidereal time
- Coordinate transformations
- Houses
- Moshier Sun, Moon, and planet calculations
- Apparent/geometric correction layers
- Rise, set, and transit calculations
- Phenomena
- Longitude crossings
- Nodes and apsides
- Orbital elements
- Fixed stars
- Aspects and chart helper objects
- Initial Swiss Ephemeris file reader foundation
- Lunar eclipse circumstances and search

Still in progress:

- Full integration of `.se1` ephemeris files into the main calculation pipeline
- JPL ephemeris support
- Full asteroid file support
- Fill fixed-star catalog support
- Solar eclipses, occultations, and full eclipse contact timelines
- Heliacal events

## License

This project is licensed under AGPL-3.0-or-later.

Swiss Ephemeris is distributed by Astrodienst AG under AGPL/commercial licensing terms. Review the original Swiss Ephemeris licensing terms before using this project in production or network-accessible services.
